<?php
    use App\Core\App;

    // toplotna prehodnost konstrukcije - podan Uw, Uw tipskega okna po SIST EN ISO 10077-1 ali Ud vrat
    $uKonstrukcije = fn($kons) => $kons->Uw ?? $kons->Uw_tip ?? $kons->Ud ?? null;

    // konstrukcije niso več shranjene v cone.json - elementi ovoja se povežejo po idKonstrukcije
    $elementiOvoja = [];
    foreach ((array)($cone ?? []) as $cona) {
        foreach ($cona->ovoj->transparentneKonstrukcije as $elementOvoja) {
            $elementiOvoja[] = ['cona' => $cona, 'element' => $elementOvoja];
        }
    }
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
<a class="button" href="<?= App::url('/pures/konstrukcije/index/' . $projectId) ?>">Netransparentne konstrukcije</a>
</p>

<h1>Analiza transparentnih konstrukcij</h1>

<table border="1" width="100%">
    <thead>
        <tr class="title"><th colspan="10"><h3>Lastnosti transparentnih konstrukcij</h3></th></tr>
        <tr>
            <th class="center w-5">Št.</th>
            <th class="center w-10">Oznaka</th>
            <th class="left">Naziv</th>
            <th class="left w-20">Vrsta po TSG</th>
            <th class="center w-10">U<sub>g</sub><br />[W/m²K]</th>
            <th class="center w-10">U<sub>f</sub><br />[W/m²K]</th>
            <th class="center w-10">U<sub>d</sub><br />[W/m²K]</th>
            <th class="center w-10">&psi;<sub>d</sub><br />[W/mK]</th>
            <th class="center w-5">g<br />[-]</th>
            <th class="center w-5">F<sub>sh</sub><br />[-]</th>
        </tr>
    </thead>
<?php
    foreach ($tKons as $k => $kons) {
?>
    <tr>
        <td class="center w-5"><?= $k+1 ?></td>
        <td class="center w-10"><b><?= h($kons->id) ?></b></td>
        <td class="left w-20"><?= h($kons->naziv) ?></td>
        <td class="left w-20"><?= h($kons->TSG->naziv ?? '') ?></td>
        <td class="center w-7"><?= isset($kons->Ug) ? $this->numFormat($kons->Ug, 2) : '' ?></td>
        <td class="center w-7"><?= isset($kons->Uf) ? $this->numFormat($kons->Uf, 2) : '' ?></td>
        <td class="center w-7"><?= isset($kons->Ud) ? $this->numFormat($kons->Ud, 2) : '' ?></td>
        <td class="center w-7"><?= isset($kons->Psi) ? $this->numFormat($kons->Psi, 2) : '' ?></td>
        <td class="center w-7"><?= isset($kons->g) ? $this->numFormat($kons->g, 2) : '' ?></td>
        <td class="center w-7"><?= isset($kons->faktorSencil) ? $this->numFormat($kons->faktorSencil, 2) : '' ?></td>
    </tr>
<?php
    }
?>
</table>
<br />

<table border="1" width="100%">
    <thead>
        <tr class="title"><th colspan="8"><h3>Ustreznost s TSG</h3></th></tr>
        <tr>
            <th class="center w-5">Št.</th>
            <th class="center w-10">Oznaka</th>
            <th class="left">Naziv</th>
            <th class="center w-10">Tipsko okno<br />b &times; h [m]</th>
            <th class="center w-10">f<sub>F</sub><br />[-]</th>
            <th class="center w-10">U<sub>w</sub>, U<sub>d</sub><br />[W/m²K]</th>
            <th class="center w-10">U<sub>max</sub><br />[W/m²K]</th>
            <th class="center w-10">Ustreznost</th>
        </tr>
    </thead>
<?php
    foreach ($tKons as $k => $kons) {
        $U = $uKonstrukcije($kons);
        $Umax = $kons->TSG->Umax ?? null;
        $ustreza = isset($U) && isset($Umax) && round($U, 2) <= $Umax;
?>
    <tr>
        <td class="center w-5"><?= $k+1 ?></td>
        <td class="center w-10"><b><?= h($kons->id) ?></b></td>
        <td class="left w-30"><?= h($kons->naziv) ?></td>
        <td class="center w-10"><?= isset($kons->tipskoOkno) ?
            $this->numFormat($kons->tipskoOkno->sirina, 2) . ' × ' . $this->numFormat($kons->tipskoOkno->visina, 2) :
            '' ?></td>
        <td class="center w-10"><?= isset($kons->tipskoOkno) ?
            $this->numFormat($kons->tipskoOkno->delezOkvirja, 2) : '' ?></td>
        <td class="center w-10"><?= isset($U) ? $this->numFormat($U, 2) : '-' ?></td>
        <td class="center w-10"><?= isset($Umax) ? $this->numFormat($Umax, 2) : '' ?></td>
<?php
        if (!isset($U)) {
?>
        <td class="center w-10">ni podatka</td>
<?php
        } else {
?>
        <td class="center w-10 <?= $ustreza ? 'green' : 'red' ?>"><?= $ustreza ? 'Ustreza' : 'Ne ustreza' ?></td>
<?php
        }
?>
    </tr>
<?php
    }
?>
</table>
<p>
    U<sub>w</sub> okna je izračunan po SIST EN ISO 10077-1 za tipsko velikost okna,
    U<sub>w</sub> = (A<sub>g</sub>&middot;U<sub>g</sub> + A<sub>f</sub>&middot;U<sub>f</sub> +
    l<sub>g</sub>&middot;&psi;<sub>d</sub>) / (A<sub>g</sub> + A<sub>f</sub>).
</p>
<?php
    if (!empty($elementiOvoja)) {
?>
<br />

<table border="1" width="100%">
    <thead>
        <tr class="title"><th colspan="9"><h3>Toplotna prehodnost elementov ovoja</h3></th></tr>
        <tr>
            <th class="center w-5">Št.</th>
            <th class="left w-20">Cona</th>
            <th class="center w-10">Element</th>
            <th class="center w-10">Konstrukcija</th>
            <th class="center w-10">A<br />[m²]</th>
            <th class="center w-10">Delež okvirja<br />[-]</th>
            <th class="center w-10">U<sub>w</sub>, U<sub>d</sub><br />[W/m²K]</th>
            <th class="center w-10">U<sub>max</sub><br />[W/m²K]</th>
            <th class="center w-10">Ustreznost</th>
        </tr>
    </thead>
<?php
        foreach ($elementiOvoja as $k => $vrstica) {
            $cona = $vrstica['cona'];
            $elementOvoja = $vrstica['element'];
            $kons = najdiKonstrukcijo($tKonsMap, $elementOvoja->idKonstrukcije);

            $Umax = $kons->TSG->Umax ?? null;
            $ustreza = isset($Umax) && round($elementOvoja->U, 2) <= $Umax;
?>
    <tr>
        <td class="center w-5"><?= $k+1 ?></td>
        <td class="left w-20"><?= h($cona->naziv) ?></td>
        <td class="center w-10"><b><?= h($elementOvoja->id) ?></b></td>
        <td class="center w-10"><?= h($kons->id) ?></td>
        <td class="center w-10"><?= $this->numFormat($elementOvoja->povrsina, 2) ?></td>
        <td class="center w-10"><?= $this->numFormat($elementOvoja->delezOkvirja, 2) ?></td>
        <td class="center w-10"><?= $this->numFormat($elementOvoja->U, 2) ?></td>
        <td class="center w-10"><?= isset($Umax) ? $this->numFormat($Umax, 2) : '' ?></td>
        <td class="center w-10 <?= $ustreza ? 'green' : 'red' ?>"><?= $ustreza ? 'Ustreza' : 'Ne ustreza' ?></td>
    </tr>
<?php
        }
?>
</table>
<?php
    }
?>
