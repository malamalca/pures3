<?php
declare(strict_types=1);

namespace App\Core\PDF;

class ChromeEngine implements PdfEngineInterface
{
    /**
     * Prefix of the temporary browser profile folders.
     *
     * @var string
     */
    private const PROFILE_PREFIX = 'chrome-';

    /**
     * PDF options
     *
     * @var array<mixed> $_options
     */
    private array $_options = [];

    /**
     * @var array<mixed> $_defaultOptions
     */
    private array $_defaultOptions = [
        'binary' => 'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'headless' => '--headless=new',
        'page-size' => 'A4',
        'orientation' => 'portrait',
        'margin-top' => 10,
        'margin-right' => 10,
        'margin-bottom' => 10,
        'margin-left' => 10,
        'background' => true,
        // Tagged (accessible) pdf - močno poveča velikost datoteke.
        'tagged' => false,
        'header-height' => 15,
        'footer-height' => 15,
        // Milliseconds of virtual time given to the page before printing.
        'virtual-time-budget' => 10000,
        // Seconds to wait for the printed file.
        'timeout' => 120,
    ];

    /**
     * Head contents of the first added page.
     *
     * @var string $_head
     */
    private string $_head = '';

    /**
     * Body contents of added pages.
     *
     * @var array<int, array<string, string>> $_pages
     */
    private array $_pages = [];

    /**
     * Running header html.
     *
     * @var string $_headerHtml
     */
    private string $_headerHtml = '';

    /**
     * Running footer html.
     *
     * @var string $_footerHtml
     */
    private string $_footerHtml = '';

    /**
     * Named page rules for pages that override size or orientation.
     *
     * @var array<string, string> $_pageRules
     */
    private array $_pageRules = [];

    /**
     * @var array<string> $_tempFiles
     */
    private array $_tempFiles = [];

    /**
     * Last error message.
     *
     * @var string $_error
     */
    private string $_error = '';

    /**
     * __construct
     *
     * @param array<mixed> $enigneOptions Array of options.
     * @return void
     */
    public function __construct($enigneOptions)
    {
        unset($enigneOptions['layout']);
        $this->options(array_replace_recursive($this->_defaultOptions, $enigneOptions));

        if (!empty($enigneOptions['headerHtml'])) {
            $this->setHeaderHtml($enigneOptions['headerHtml']);
        }
        if (!empty($enigneOptions['footerHtml'])) {
            $this->setFooterHtml($enigneOptions['footerHtml']);
        }
    }

    /**
     * __destruct
     *
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->_tempFiles as $fileName) {
            if (file_exists($fileName)) {
                unlink($fileName);
            }
        }
    }

    /**
     * Get/set options.
     *
     * @param array<mixed> $values Options values.
     * @return mixed
     */
    public function options($values = null)
    {
        if ($values === null) {
            return $this->_options;
        }
        $this->_options = $values;

        return $this;
    }

    /**
     * Set single options.
     *
     * @param array<mixed> $values Options values.
     * @return $this
     */
    public function setOptions($values)
    {
        $this->_options = array_replace_recursive($this->_options, $values);

        return $this;
    }

    /**
     * Get last error.
     *
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Add page with html contents
     *
     * Headless chrome prints a single document, so pages are only collected here
     * and merged into one html file on save.
     *
     * @param string $html Html page content.
     * @param array<mixed> $options Page options. Supported: page-size, orientation.
     * @return void
     */
    public function newPage($html, $options = [])
    {
        if ($this->_head === '') {
            $this->_head = $this->extractHead($html);
        }

        $this->_pages[] = [
            'body' => $this->extractBody($html),
            'pageName' => $this->registerPageRule($options),
        ];
    }

    /**
     * Save PDF as file.
     *
     * @param string $filename Filename.
     * @return bool
     */
    public function saveAs($filename)
    {
        if (empty($this->_pages)) {
            throw new \Exception('No pages to print.');
        }

        $this->cleanupProfiles();

        $sourceFile = TMP . uniqid('', true) . '.html';
        file_put_contents($sourceFile, $this->buildDocument());
        $this->_tempFiles[] = $sourceFile;

        $userDataDir = TMP . self::PROFILE_PREFIX . uniqid('', true);
        $this->_error = '';

        if (file_exists($filename)) {
            unlink($filename);
        }

        $output = '';
        $returnCode = $this->execute(
            $this->buildCommand($sourceFile, $filename, $userDataDir),
            $output
        );

        // The launcher process returns immediately, printing is done by a detached
        // browser process, so the result file has to be waited for.
        $printed = $returnCode === 0 && $this->waitForFile($filename, (int)$this->_options['timeout']);

        // The browser is usually still exiting and holding its profile files.
        $attempts = 5;
        while ($attempts-- > 0) {
            usleep(300000);
            $this->removeDirectory($userDataDir);
            if (!is_dir($userDataDir)) {
                break;
            }
        }

        if (!$printed) {
            $this->_error = sprintf('Headless chrome failed (exit code %d): %s', $returnCode, $output);

            throw new \Exception($this->_error);
        }

        return true;
    }

    /**
     * Waits until the browser has written the whole pdf file.
     *
     * @param string $filename Target pdf file.
     * @param int $timeout Seconds to wait.
     * @return bool
     */
    private function waitForFile($filename, $timeout)
    {
        $deadline = microtime(true) + $timeout;
        $lastSize = -1;
        $stable = 0;

        while (microtime(true) < $deadline) {
            usleep(200000);
            clearstatcache(true, $filename);
            if (!file_exists($filename)) {
                continue;
            }

            $size = (int)filesize($filename);
            // Size has to stay the same for a few checks - the file is
            // created before it is fully written.
            $stable = $size > 0 && $size === $lastSize ? $stable + 1 : 0;
            $lastSize = $size;

            if ($stable >= 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runs the browser. The command is passed as an array, so no shell is
     * involved and arguments need no escaping.
     *
     * @param array<string> $command Command with arguments.
     * @param string $output Collected stdout and stderr.
     * @return int Exit code.
     */
    private function execute($command, &$output)
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes = [];
        $process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            $output = 'Cannot start ' . $command[0];

            return -1;
        }

        fclose($pipes[0]);
        $output = (string)stream_get_contents($pipes[1]) . (string)stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }

    /**
     * Set page header html.
     *
     * @param string $html Html page content.
     * @return void
     */
    public function setHeaderHtml($html)
    {
        $this->_headerHtml = $this->normalizeRunningHtml($html);
    }

    /**
     * Set page footer html.
     *
     * @param string $html Html page content.
     * @return void
     */
    public function setFooterHtml($html)
    {
        $this->_footerHtml = $this->normalizeRunningHtml($html);
    }

    /**
     * Builds chrome command line.
     *
     * @param string $sourceFile Source html file.
     * @param string $targetFile Target pdf file.
     * @param string $userDataDir Temporary profile folder.
     * @return array<string>
     */
    private function buildCommand($sourceFile, $targetFile, $userDataDir)
    {
        $arguments = [
            (string)$this->_options['binary'],
            (string)$this->_options['headless'],
            '--disable-gpu',
            '--disable-extensions',
            '--hide-scrollbars',
            '--no-first-run',
            '--no-default-browser-check',
            '--allow-file-access-from-files',
            '--run-all-compositor-stages-before-draw',
            // Older chrome builds use --print-to-pdf-no-header, newer ones --no-pdf-header-footer.
            '--print-to-pdf-no-header',
            '--no-pdf-header-footer',
            '--virtual-time-budget=' . (int)$this->_options['virtual-time-budget'],
            '--user-data-dir=' . $userDataDir,
        ];

        if (empty($this->_options['tagged'])) {
            // Struktura dostopnosti podvoji do peterokratno velikost pdf-ja pri tabelah
            $arguments[] = '--disable-pdf-tagging';
        }

        $arguments[] = '--print-to-pdf=' . $targetFile;
        $arguments[] = 'file:///' . str_replace('\\', '/', $sourceFile);

        return $arguments;
    }

    /**
     * Merges collected pages into a single printable html document.
     *
     * @return string
     */
    private function buildDocument()
    {
        $pages = '';
        foreach ($this->_pages as $page) {
            $class = 'pdf-page';
            if ($page['pageName'] !== '') {
                $class .= ' pdf-page-' . $page['pageName'];
            }
            $pages .= '<div class="' . $class . '">' . $page['body'] . '</div>' . PHP_EOL;
        }

        $running = '';
        if ($this->_headerHtml !== '') {
            $running .= '<div class="pdf-running-header">' . $this->_headerHtml . '</div>' . PHP_EOL;
        }
        if ($this->_footerHtml !== '') {
            $running .= '<div class="pdf-running-footer">' . $this->_footerHtml . '</div>' . PHP_EOL;
        }

        return '<!doctype html>' . PHP_EOL .
            '<html lang="sl">' . PHP_EOL .
            '<head>' . PHP_EOL .
            '<meta charset="utf-8">' . PHP_EOL .
            $this->_head . PHP_EOL .
            '<style>' . $this->buildStyle() . '</style>' . PHP_EOL .
            '</head>' . PHP_EOL .
            '<body translate="no">' . PHP_EOL .
            $running .
            $pages .
            '</body>' . PHP_EOL .
            '</html>';
    }

    /**
     * Builds print stylesheet with page setup, page breaks and running header/footer.
     *
     * @return string
     */
    private function buildStyle()
    {
        $headerHeight = $this->_headerHtml !== '' ? (float)$this->_options['header-height'] : 0;
        $footerHeight = $this->_footerHtml !== '' ? (float)$this->_options['footer-height'] : 0;

        $style = '@page {' .
            'size: ' . $this->pageSize($this->_options) . ';' .
            'margin: ' . ((float)$this->_options['margin-top'] + $headerHeight) . 'mm ' .
                (float)$this->_options['margin-right'] . 'mm ' .
                ((float)$this->_options['margin-bottom'] + $footerHeight) . 'mm ' .
                (float)$this->_options['margin-left'] . 'mm;' .
            '}';

        foreach ($this->_pageRules as $name => $size) {
            $style .= '@page ' . $name . ' { size: ' . $size . '; }' .
                '.pdf-page-' . $name . ' { page: ' . $name . '; }';
        }

        $style .= 'html, body { margin: 0; padding: 0; }' .
            '.pdf-page { break-after: page; page-break-after: always; }' .
            '.pdf-page:last-of-type { break-after: auto; page-break-after: auto; }';

        if ($this->_headerHtml !== '') {
            // Running elements are drawn into the page margin reserved above.
            $style .= '.pdf-running-header {' .
                'position: fixed; left: 0; right: 0; overflow: hidden;' .
                'top: -' . $headerHeight . 'mm; height: ' . $headerHeight . 'mm;' .
                '}';
        }
        if ($this->_footerHtml !== '') {
            $style .= '.pdf-running-footer {' .
                'position: fixed; left: 0; right: 0; overflow: hidden;' .
                'bottom: -' . $footerHeight . 'mm; height: ' . $footerHeight . 'mm;' .
                '}';
        }

        if (!empty($this->_options['background'])) {
            $style .= 'html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }';
        }

        return $style;
    }

    /**
     * Registers a named page rule when a page overrides size or orientation.
     *
     * @param array<mixed> $options Page options.
     * @return string Page rule name or empty string for the default page.
     */
    private function registerPageRule($options)
    {
        if (empty($options['page-size']) && empty($options['orientation'])) {
            return '';
        }

        $size = $this->pageSize($options + $this->_options);
        $name = 'p' . substr(md5($size), 0, 8);
        $this->_pageRules[$name] = $size;

        return $name;
    }

    /**
     * Formats css page size from options.
     *
     * @param array<mixed> $options Options containing page-size and orientation.
     * @return string
     */
    private function pageSize($options)
    {
        $size = (string)($options['page-size'] ?? 'A4');
        $orientation = strtolower((string)($options['orientation'] ?? 'portrait'));
        if (in_array($orientation, ['landscape', 'l'])) {
            $size .= ' landscape';
        }

        return $size;
    }

    /**
     * Returns contents of the head element, without the meta charset tag.
     *
     * @param string $html Page html.
     * @return string
     */
    private function extractHead($html)
    {
        if (!preg_match('/<head[^>]*>(.*)<\/head>/is', $html, $matches)) {
            return '';
        }

        return (string)preg_replace('/<meta[^>]+charset[^>]*>/i', '', $matches[1]);
    }

    /**
     * Returns contents of the body element. Falls back to the whole html when
     * the page is only a fragment.
     *
     * @param string $html Page html.
     * @return string
     */
    private function extractBody($html)
    {
        if (!preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $matches)) {
            return $html;
        }

        // Layouts may contain stray body tags - they must not end up nested in the document.
        return (string)preg_replace('/<\/?body[^>]*>/i', '', $matches[1]);
    }

    /**
     * Converts header/footer definition into printable html. Json definitions
     * with a base64 encoded image are turned into an img tag.
     *
     * @param string $html Html or json image definition.
     * @return string
     */
    private function normalizeRunningHtml($html)
    {
        if (substr($html, 0, 2) == '{"') {
            $data = json_decode($html, true);
            if ($data) {
                $binary = base64_decode($data['image']);
                $type = $this->getImageType($binary);
                if ($type) {
                    $html = '<img src="data:image/' . $type . ';base64,' . $data['image'] . '" />';
                }
            }
        }

        return $html;
    }

    /**
     * Returns image type
     *
     * @param string $binary Binary data
     * @return string|bool
     */
    private function getImageType($binary)
    {
        $types = [
            'jpeg' => "\xFF\xD8\xFF",
            'gif' => 'GIF',
            'png' => "\x89\x50\x4e\x47\x0d\x0a",
        ];

        $found = false;
        foreach ($types as $type => $header) {
            if (strpos($binary, $header) === 0) {
                $found = $type;
                break;
            }
        }

        return $found;
    }

    /**
     * Removes browser profiles that a previous run could not clean up.
     *
     * @return void
     */
    private function cleanupProfiles()
    {
        $folders = glob(TMP . self::PROFILE_PREFIX . '*', GLOB_ONLYDIR);
        if ($folders === false) {
            return;
        }

        foreach ($folders as $folder) {
            if (filemtime($folder) < time() - 3600) {
                $this->removeDirectory($folder);
            }
        }
    }

    /**
     * Recursively removes the temporary chrome profile folder.
     *
     * @param string $path Folder path.
     * @return void
     */
    private function removeDirectory($path)
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            $fullPath = $path . DS . $entry;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } else {
                // The browser may still be shutting down and holding its files.
                @unlink($fullPath);
            }
        }

        @rmdir($path);
    }
}
