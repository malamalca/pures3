<?php
    use App\Lib\Calc;

    $elementiOvoja = array_merge($cona->ovoj->netransparentneKonstrukcije, $cona->ovoj->transparentneKonstrukcije);

?>
<h1>Ovoj cone "<?= h($cona->naziv) ?>"</h1>


<table border="1">
    <tr>
        <td colspan="4">Zaporedna št. konstrukcije</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $elementOvoja->idKonstrukcije . '</td>', $elementiOvoja)) ?>
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
        <td class="right">W/m2K</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->U, 3) . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr>
        <td colspan="2">Površina</td>
        <td>A</td>
        <td class="right">m2</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->povrsina, 1) . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr>
        <td colspan="2">Faktor</td>
        <td>b</td>
        <td class="right">&nbsp;</td>
        <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->b, 2) . '</td>', $elementiOvoja)) ?>
    </tr>
    <tr><td colspan="10">&nbsp;</td></tr>
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
    <tr><td colspan="10">&nbsp;</td></tr>

    <tr>
        <td colspan="4">Faktor senčenja okoliških ovir   Fsh,glob,ov,m</td>
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
    <tr><td colspan="10">&nbsp;</td></tr>


    <tr>
        <td colspan="3">Mesečno sončno obsevanje H<sub>sol,m</sub> (Wh/m²m)</td>
        <td>št. dni</td>
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

    <tr>
        <td colspan="2">Transmisijske toplotne izgube Qtr,m (kWh/m)</td>
        <td class="center">&Delta;T</td>
        <td class="center">št. dni</td>
        <td colspan="3">OGREVANJE</td>
    </tr>
    <?php
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
    ?>
        <tr>
            <td></td>
            <td class="center"><?= Calc::MESECI[$mesec] ?></td>
            <td class="center"><?= $cona->deltaTOgrevanje[$mesec] ?></td>
            <td class="center"><?= $daysInMonth ?></td>
            <?= implode(PHP_EOL, array_map(fn($elementOvoja) => '<td class="center">' . $this->numFormat($elementOvoja->transIzgubeOgrevanje[$mesec], 1) . '</td>', $elementiOvoja)) ?>
            <td class="center"><?= $this->numFormat($cona->transIzgubeOgrevanje[$mesec], 1) ?></td>
        </tr>
    <?php
        }
    ?>

    <tr>
        <td colspan="2">Transmisijske toplotne izgube Qtr,m (kWh/m)</td>
        <td class="center">&Delta;T</td>
        <td class="center">št. dni</td>
        <td colspan="3">HLAJENJE</td>
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

    <tr>
        <td colspan="3">Dobitki sončnega obsevanja Qsol,m (kWh/m)</td>
        <td>št. dni</td>
        <td colspan="3">OGREVANJE</td>
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

    <tr>
        <td colspan="3">Dobitki sončnega obsevanja Qsol,m (kWh/m)</td>
        <td>št. dni</td>
        <td colspan="3">HLAJENJE</td>
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
            <td class="center"><?= $this->numFormat($cona->solarniDobitkiHlajenje[$mesec], 1) ?></td>
        </tr>
    <?php
        }
    ?>
</table>