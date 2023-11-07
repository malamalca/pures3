<?php
declare(strict_types=1);

namespace App\Core;

use JsonSchema\Validator;

class Command
{
    /**
     * Run command
     *
     * @return void
     */
    public function run()
    {
    }

    /**
     * Print string to console
     *
     * @param string $str Output string
     * @param string $type Output type
     * @return int
     */
    public function out($str, $type = 'info')
    {
        $ret = 0;
        switch ($type) {
            case 'error': //error
                echo "\033[31m$str \033[0m\n";
                $ret = -1;
                break;
            case 'success': //success
                echo "\033[32m$str \033[0m\n";
                $ret = 1;
                break;
            case 'warning': //warning
                echo "\033[33m$str \033[0m\n";
                break;
            case 'info': //info
                echo "\033[36m$str \033[0m\n";
                break;
        }

        return $ret;
    }

    /**
     * Validate json object to schema
     *
     * @param \stdClass|array $json Json object
     * @param string $schema Schema name
     * @param string $area Pures or Hrup
     * @return bool
     */
    public function validateSchema(\stdClass|array $json, string $schema, string $area = 'Pures')
    {
        $validator = new Validator();
        $schemaFile = SCHEMAS . $area . DS . $schema . 'Schema.json';
        $schemaContents = (string)file_get_contents($schemaFile);
        $validator->validate($json, json_decode($schemaContents));
        if (!$validator->isValid()) {
            $this->out(sprintf('PREVERJANJE SHEME :: Datoteka "%s" vsebuje napake.', $schema), 'error');
            foreach ($validator->getErrors() as $error) {
                $this->out(sprintf('[%s] %s', $error['property'], $error['message']), 'info');
            }

            return false;
        } else {
            return true;
        }
    }
}
