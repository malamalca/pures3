<?php
    use App\Lib\Calc;
    use App\Core\App;

    array_walk($cona->ovoj->transparentneKonstrukcije, function ($value, $key) use ($cona) { $cona->ovoj->transparentneKonstrukcije[$key]->povezavaIzpis = 'oknavrata'; });
    $elementiOvoja = array_merge($cona->ovoj->netransparentneKonstrukcije, $cona->ovoj->transparentneKonstrukcije);
    $stElementov = count($elementiOvoja);
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
<a class="button" href="<?= App::url('/pures/cone/analiza/' . $projectId . '/' . $cona->id) ?>">Analiza cone "<?= $cona->id ?>"</a>
<a class="button active" href="<?= App::url('/pures/cone/ovoj/' . $projectId . '/' . $cona->id) ?>">Analiza ovoja cone "<?= $cona->id ?>"</a>
</p>
<h1>Ovoj cone "<?= h($cona->naziv) ?>"</h1>
<?php
    // prelom po listih - maksimalno število elementov v tabeli (podatki elementa so v vertikalo)
    $maxSteviloElementov = $this instanceof \App\Core\PdfView ? 9 : 100;

    $vsiElementiOvoja = $elementiOvoja;
    $pozicijaGrupe = 0;
    $steviloSkupin = ceil($stElementov / $maxSteviloElementov);

    for ($stKoraka = 0; $stKoraka < $steviloSkupin; $stKoraka++) {
        $elementiOvoja = array_slice($vsiElementiOvoja, $stKoraka * $maxSteviloElementov, $maxSteviloElementov);
        $jeZadnjaGrupa = $stKoraka == $steviloSkupin - 1;
        if ($steviloSkupin > 1) {
            $stElementov = $maxSteviloElementov;
            if ($jeZadnjaGrupa) {
                $stElementov = count($vsiElementiOvoja) % $maxSteviloElementov;
            }
        }
?>

<table border="1">
    <tr>
        <th>ID</th>
        <th>A [m2]</th>
        <th>U [W/m2K]</th>
        <th>Tzun [°C]</th>
        <th>Tnotr [°C]</th>
        <th>ΔT [K]</th>

        <th>W/K</th>
        <th>W</th>
    </tr>
    <?php
        $Tnotr = $cona->notranjaTOgrevanje;

        $Povoj = 0;
        foreach ($elementiOvoja as $elementOvoja) {
            if (isset($elementOvoja->konstrukcija->TSG->tip) && $elementOvoja->konstrukcija->TSG->tip != 'zunanja') {
                $Tzun = 0;
            } else {
                $Tzun = $okolje->projektnaZunanjaT;
            }
            $Tdelta = abs($Tnotr) + abs($Tzun);

            $Povoj += $elementOvoja->povrsina * $elementOvoja->U * $Tdelta;

    ?>
    <tr>
        <td><?= h($elementOvoja->id) ?></td>
        <td><?= $this->numFormat($elementOvoja->povrsina, 1) ?></td>
        <td><?= $this->numFormat($elementOvoja->U, 2) ?></td>
        <td><?= $this->numFormat($Tzun, 1) ?></td>
        <td><?= $this->numFormat($Tnotr, 1) ?></td>
        <td><?= $this->numFormat($Tdelta, 1) ?></td>

        <td><?= $this->numFormat($elementOvoja->povrsina * $elementOvoja->U, 2) ?></td>
        <td><?= $this->numFormat($elementOvoja->povrsina * $elementOvoja->U * $Tdelta, 2) ?></td>
    </tr>
    <?php
        }
    ?>
    <tr>
        <th colspan="7">Skupaj ovoj [W]:</th>
        <th><?= $this->numFormat($Povoj, 2) ?></th>
    </tr>
</table>
<br />
<?php
    }
?>

<h1>Prezračevanje "<?= h($cona->naziv) ?>"</h1>
<table border="1">
    <tr>
        <th>V [m3/h]</th>
        <th>C<sub>air</sub> [Wh/m2K]</th>
        <th>ΔT [K]</th>
        <th>izkoristek</th>

        <th>W</th>
    </tr>
    <?php
        $Cair = 0.33;
        $Tzun = $okolje->projektnaZunanjaT;
        $deltaT = abs($Tzun) + abs($cona->notranjaTOgrevanje);
        $izkoristek = $cona->prezracevanje->izkoristek ?? 0;

        $Pvent = $cona->volumenZrakaOgrevanje * $Cair * $deltaT * (1 - $izkoristek);
    ?>
    <tr>
        <td><?= $this->numFormat($cona->volumenZrakaOgrevanje, 2) ?></td>
        <td><?= $this->numFormat($Cair, 2) ?></td>
        <td><?= $this->numFormat($deltaT, 1) ?></td>
        <td><?= $this->numFormat($izkoristek, 2) ?></th>

        <td><?= $this->numFormat($Pvent, 2) ?></td>
    </tr>
    <tr>
        <th colspan="4">Skupaj prezr. [W]:</th>
        <th><?= $this->numFormat($Pvent, 2) ?></th>
    </tr>
</table>
<br />


<h1>Moč za cono "<?= h($cona->naziv) ?>": <?= $this->numFormat(($Povoj + $Pvent) / 1000, 1) ?> kW</h1>