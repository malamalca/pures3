<?php
    use App\Lib\Calc;
    use App\Core\App;

    $elementiOvoja = array_merge($cona->ovoj->netransparentneKonstrukcije, $cona->ovoj->transparentneKonstrukcije);
    $stElementov = count($elementiOvoja);

?>
<h1>Ovoj cone "<?= h($cona->naziv) ?>"</h1>
<p class="actions">
<a class="button" href="<?= App::url('/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>


<table border="1">
    <tr>
        <th colspan="4">Zaporedna št. konstrukcije</th>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) =>
            '<th class="center">' . 
            '<a class="button" href="' .
            App::url('/konstrukcije/view/' . $projectId . '/' . $elementOvoja->idKonstrukcije) .
            '">' . $elementOvoja->idKonstrukcije . '</a>' . '</th>', $elementiOvoja)) ?>
    </tr>
    <tr>
        <td colspan="4">Št. enakih</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $elementOvoja->stevilo . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr>
        <td colspan="4">Orientacija</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $elementOvoja->orientacija . '</td>', $elementiOvoja)) ?>
            
    </tr>
    <tr>
        <td colspan="3">Naklon</td>
        <td class="right">°</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $elementOvoja->naklon . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr>
        <td colspan="2">Toplotna prehodnost</td>
        <td>U</td>
        <td class="right">W/m²K</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->U, 3) . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr>
        <td colspan="2">Površina</td>
        <td>A</td>
        <td class="right">m²</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->povrsina, 1) . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr>
        <td colspan="2">Faktor</td>
        <td>b</td>
        <td class="right">&nbsp;</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->b, 2) . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr><td colspan="<?= 5 + $stElementov ?>">&nbsp;</td></tr>

    <tr>
        <td colspan="2">&nbsp;</td>
        <td class="right">U×A×b</td>
        <td class="right">W/K</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->b * $elementOvoja->povrsina * $elementOvoja->U, 1) . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr>
        <td colspan="2">&nbsp;</td>
        <td class="right">d<sub>f</sub></td>
        <td class="right">m</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . (isset($elementOvoja->df) ? $this->numFormat($elementOvoja->df, 1) : '') . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr><td colspan="<?= 5 + $stElementov ?>">&nbsp;</td></tr>

    <tr>
        <th colspan="4">Faktor senčenja okoliških ovir F<sub>sh,glob,ov,m</sub></th>
        <th colspan="<?= $stElementov + 1 ?>"></th>
    </tr>
    <?php
        foreach (array_keys(Calc::MESECI) as $mesec) {
    ?>
        <tr>
            <td colspan="3"></td>
            <td class="center"><?= Calc::MESECI[$mesec] ?></td>
            <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->faktorSencenja[$mesec], 3) . '</td>', $elementiOvoja)) ?>
        </tr>
    <?php
        }
    ?>
    <tr><td colspan="<?= 5 + $stElementov ?>">&nbsp;</td></tr>


    <tr>
        <th colspan="3">Mesečno sončno obsevanje H<sub>sol,m</sub> (Wh/m²m)</th>
        <th>št. dni</th>
        <th colspan="<?= $stElementov + 1 ?>"></th>
    </tr>
    <?php
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
    ?>
        <tr>
            <td colspan="2"></td>
            <td class="center"><?= Calc::MESECI[$mesec] ?></td>
            <td class="center"><?= $daysInMonth ?></td>
            <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->soncnoObsevanje[$mesec] * $daysInMonth, 0) . '</td>', $elementiOvoja)) ?>
        </tr>
    <?php
        }
    ?>
    <tr><td colspan="<?= 5 + $stElementov ?>">&nbsp;</td></tr>


    <tr>
        <th colspan="2">Transmisijske toplotne izgube Q<sub>tr,m</sub> (kWh/m)</th>
        <th class="center">&Delta;T</th>
        <th class="center">št. dni</th>
        <th colspan="<?= $stElementov ?>">OGREVANJE</th>
        <th class="center">Skupaj</th>
    </tr>
    <?php
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
    ?>
        <tr>
            <td></td>
            <td class="center w-5"><?= Calc::MESECI[$mesec] ?></td>
            <td class="center"><?= $cona->deltaTOgrevanje[$mesec] ?></td>
            <td class="center"><?= $daysInMonth ?></td>
            <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->transIzgubeOgrevanje[$mesec], 1) . '</td>', $elementiOvoja)) ?>
            <td class="center"><?= $this->numFormat($cona->transIzgubeOgrevanje[$mesec], 1) ?></td>
        </tr>
    <?php
        }
    ?>
    <tr><td colspan="<?= 5 + $stElementov ?>">&nbsp;</td></tr>


    <tr>
        <th colspan="2">Transmisijske toplotne izgube Q<sub>tr,m</sub> (kWh/m)</th>
        <th class="center">&Delta;T</th>
        <th class="center">št. dni</th>
        <th colspan="<?= $stElementov ?>">HLAJENJE</th>
        <th class="center">Skupaj</th>
    </tr>
    <?php
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
    ?>
        <tr>
            <td></td>
            <td class="center"><?= Calc::MESECI[$mesec] ?></td>
            <td class="center"><?= $cona->deltaTHlajenje[$mesec] ?></td>
            <td class="center"><?= $daysInMonth ?></td>
            <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->transIzgubeHlajenje[$mesec], 1) . '</td>', $elementiOvoja)) ?>
            <td class="center"><?= $this->numFormat($cona->transIzgubeHlajenje[$mesec], 1) ?></td>
        </tr>
        </tr>
    <?php
        }
    ?>
    <tr><td colspan="<?= 5 + $stElementov ?>">&nbsp;</td></tr>

    <tr>
        <th colspan="3">Dobitki sončnega obsevanja Qsol,m (kWh/m)</th>
        <th>št. dni</th>
        <th colspan="<?= $stElementov ?>">OGREVANJE</th>
        <th class="center">Skupaj</th>
    </tr>
    <?php
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
    ?>
        <tr>
            <td colspan="2"></td>
            <td class="center"><?= Calc::MESECI[$mesec] ?></td>
            <td class="center"><?= $daysInMonth ?></td>
            <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->solarniDobitkiOgrevanje[$mesec], 1) . '</td>', $elementiOvoja)) ?>
            <td class="center"><?= $this->numFormat($cona->solarniDobitkiOgrevanje[$mesec], 1) ?></td>
        </tr>
    <?php
        }
    ?>
    <tr><td colspan="<?= 5 + $stElementov ?>">&nbsp;</td></tr>

    <tr>
        <th colspan="3">Dobitki sončnega obsevanja Qsol,m (kWh/m)</th>
        <th>št. dni</td>
        <th colspan="<?= $stElementov ?>">HLAJENJE</th>
        <th class="center">Skupaj</th>
    </tr>
    <?php
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
    ?>
        <tr>
            <td colspan="2"></td>
            <td class="center"><?= Calc::MESECI[$mesec] ?></td>
            <td class="center"><?= $daysInMonth ?></td>
            <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->solarniDobitkiHlajenje[$mesec], 1) . '</td>', $elementiOvoja)) ?>
            <th class="center"><?= $this->numFormat($cona->solarniDobitkiHlajenje[$mesec], 1) ?></th>
        </tr>
    <?php
        }
    ?>
</table>