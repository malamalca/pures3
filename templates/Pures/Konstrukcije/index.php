<?php
    use \App\Core\App;
    use \App\Lib\Calc;
    use \App\Lib\CalcKonstrukcije;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<h1>Seznam konstrukcij projekta</h1>

<table border="1">
    <tr>
        <?= implode(PHP_EOL, array_map(fn($kons) =>
            '<th class="left">' . 
            '<a class="button" href="' .
            App::url('/pures/konstrukcije/view/' . $projectId . '/' . $kons->id) .
            '">' . $kons->id . '</a>' . '</th>', $konstrukcije)) ?>
    </tr>
    <tr>
        <?= implode(PHP_EOL, array_map(fn($kons) =>
            '<td class="left">' . h($kons->naziv) . '</td>', $konstrukcije)) ?>
    </tr>
    <tr>
        <?= implode(PHP_EOL, array_map(fn($kons) =>
            '<td class="left">' . h($kons->TSG->naziv) . '<br />' .
            '<div class="nowrap">Tip: ' . h($kons->TSG->tip) . '</div>' .
            '<div class="nowrap">Dobitek SS: ' . (!empty($kons->TSG->dobitekSS) ? 'DA' : 'NE') . '</div>' .
            '<div class="nowrap">U<sub>max</sub>=' . $this->numFormat($kons->TSG->Umax, 2) . ' W/m²K</div><br />' .
            '</td>', $konstrukcije)) ?>
    </tr>
    <tr>
        <?= implode(PHP_EOL, array_map(fn($kons) =>
            '<td class="left' . ($kons->U < $kons->TSG->Umax ? '' : ' red') . '">U = ' . $this->numFormat($kons->U, 2) . ' W/m²K</td>', $konstrukcije)) ?>
    </tr>
</table>
