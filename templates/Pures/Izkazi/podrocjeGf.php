<?php
    use App\Core\App;
?>
<p class="actions">
    <a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
    <a class="button" href="<?= App::url('/pures/izkazi/splosniPodatki/' . $projectId) ?>">Splošni podatki</a>
    <a class="button active" href="<?= App::url('/pures/izkazi/podrocjeGf/' . $projectId) ?>">Področje GF</a>
    <a class="button" href="<?= App::url('/pures/izkazi/podrocjeSNES/' . $projectId) ?>">Področje SNES</a>
</p>
<?php
    if ($stavba->vrsta == 'zahtevna') {
?>
    <h1>Energijska učinkovitost energetsko zahtevne stavbe – za področje gradbene fizike</h1>
<?php
    } else {
?>
    <h1>Energijska učinkovitost energetsko manj zahtevne stavbe – za področje gradbene fizike</h1>
<?php
    }
?>

<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr class="title"><th colspan="4"><h3>Kazalniki</h3></th></tr>
    <tr>
        <th colspan="4">Toplotna prehodnost gradbenih konstrukcij in gradnikov ovoja stavb U (W/(m²K)):</th>
    </tr>
    </thead>
    <?php
        $i = 0;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-70" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-20 center">U<sub>op</sub>, U<sub>w</sub>, U<sub>d</sub><br />(W/(m²K))</td>
        <td class="w-10 center">Ustreza</td>
    </tr>
    <?php
            $konstrukcije = array_combine(array_map(fn($k) => $k->id, $ntKons), $ntKons);
            foreach ($cona->ovoj->netransparentneKonstrukcije as $elementOvoja) {
                $konstrukcija = $konstrukcije[$elementOvoja->idKonstrukcije];
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-65"><?= h($konstrukcija->id) ?> - <?= h($konstrukcija->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->U, 2) ?></td>
        <td class="w-10 center">
            <span title="U < <?= $this->numFormat($konstrukcija->TSG->Umax, 2) ?>">
            <b class="<?= $elementOvoja->U < $konstrukcija->TSG->Umax ? 'green' : 'red' ?>">
            <?= $elementOvoja->U < $konstrukcija->TSG->Umax ? '&#10003;' : '&#10006;' ?>
            </b></span>
        </td>
    </tr>
    <?php
                $i++;
            }
            $konstrukcije = array_combine(array_map(fn($k) => $k->id, $tKons), $tKons);
            foreach ($cona->ovoj->transparentneKonstrukcije as $elementOvoja) {
                $konstrukcija = $konstrukcije[$elementOvoja->idKonstrukcije];
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-65"><?= h($konstrukcija->id) ?> - <?= h($konstrukcija->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->U, 2) ?></td>
        <td class="w-10 center">
            <span title="U < <?= $this->numFormat($konstrukcija->TSG->Umax, 2) ?>">
            <b class="<?= $elementOvoja->U < $konstrukcija->TSG->Umax ? 'green' : 'red' ?>">
            <?= $elementOvoja->U < $konstrukcija->TSG->Umax ? '&#10003;' : '&#10006;' ?>
            </b></span>
        </td>
    </tr>
    <?php
                $i++;
            }
        }
    ?>
</table>

<!-- ---------------------------------------------------------------------------- -->
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="4">Linijske <i class="big serif">&Psi;</i> (W/(m K)) in točkovne <i class="big serif">&Chi;</i> (W/K) toplotne prehodnosti toplotnih mostov:</th>
    </tr>
    </thead>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&#10003;</span></td>
        <td class="w-65">Določitev po poenostavljeni metodi (s konstantno vrednostjo):</td>
        <td class="w-20 center">&Delta;<i class="big serif">&Psi;</i> (W/(m K))</td>
        <td class="w-10 center"><?= $this->numFormat($cone[0]->deltaPsi, 2) ?></td>
    </tr>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&nbsp;</span></td>
        <td colspan="3">Natančnejši izračun</td>
    </tr>
</table>

<!-- ---------------------------------------------------------------------------- -->
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="6">Preverjanje prehoda vodne pare:</th>
    </tr>
    </thead>
    <?php
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-60" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-10 center">Kondenzacija se pojavi</td>
        <td class="w-10 center">Največja količina kondenzata</td>
        <td class="w-10 center">f<sub>Rsi</sub></td>
        <td class="w-10 center">Ustreza</td>
    </tr>
    <?php
            $konstrukcije = array_combine(array_map(fn($k) => $k->id, $ntKons), $ntKons);
            foreach ($cona->ovoj->netransparentneKonstrukcije as $i => $elementOvoja) {
                $konstrukcija = $konstrukcije[$elementOvoja->idKonstrukcije];

                if (!isset($konstrukcija->TSG->kontrolaKond) || $konstrukcija->TSG->kontrolaKond !== false) {

    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-55"><?= h($konstrukcija->naziv) ?></td>
        <td class="w-10 center">
            <i class="<?= empty($konstrukcija->gm) ? 'green' : 'black' ?>">
            <?= empty($konstrukcija->gm) ? '&#10006;' : '&#10003;'  ?>
            </i>
        </td>
        <td class="w-10 center"><?= $this->numFormat($konstrukcija->maxGm ?? 0, 1) ?></td>
        <td class="w-10 center"><?= $this->numFormat($konstrukcija->fRsi[0], 3) ?></td>
        <td class="w-10 center">
            <span title="fRsi > <?= $this->numFormat($okolje->limitfRsi, 3) ?>">
            <b class="<?= $konstrukcija->fRsi[0] < $okolje->limitfRsi ? 'red' : 'green' ?>">
                <?= $konstrukcija->fRsi[0] > $okolje->limitfRsi ? '&#10003;' : '&#10006;' ?>
                </b></span>
        </td>
    </tr>
    <?php
                }
            }
        }
    ?>
</table>

<!-- ---------------------------------------------------------------------------- -->
<?php
    if ($stavba->vrsta == 'zahtevna') {
?>
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="4">Faktor toplotne stabilnosti f (-):</th>
    </tr>
    </thead>
    <tr>
        <td class="w-60" colspan="2">konstrukcija</td>
        <td class="w-20 center">f (-)</td>
        <td class="w-20 center">ustreza</td>
    </tr>

    <?php
        foreach ($cone as $cona) {
    ?>

    <?php
            $i = 0;
            foreach (array_filter($ntKons, fn($k) => empty($k->TSG->izracunToplotneStabilnosti) ? null : $k) as $konstrukcija) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-55"><?= h($konstrukcija->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($konstrukcija->f, 2) ?></td>
        <td class="w-20 center"><?= $konstrukcija->f < 0.5 ? 'DA' : 'NE' ?></td>
    </tr>
    <?php
                $i++;
            }
        }
    ?>
</table>
<?php
    }
?>

<!-- ---------------------------------------------------------------------------- -->
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="7">Specifični koeficient transmisijskih toplotnih izgub H'tr (W/(m²K)):</th>
    </tr>
    </thead>
    <tr>
        <td class="w-60" colspan="2">Energetska cona oziroma stavba</td>
        <td class="w-10 center">XH'tr<br />(-)</td>
        <td class="w-10 center">H'tr<br />(W/(m²K))</td>
        <td class="w-10 center">H'tr,dov<br />(W/(m²K))</td>
        <td class="w-10 center">Ustreza</td>
    </tr>

    <tr>
        <td class="w-5 center"></td>
        <td class="w-55">STAVBA</td>
        <td class="w-10 center"><?= $this->numFormat($stavba->X_Htr)  ?></td>
        <td class="w-10 center"><?= $this->numFormat($stavba->specKoeficientTransmisijskihIzgub, 3) ?></td>
        <td class="w-10 center"><?= $this->numFormat($stavba->dovoljenSpecKoeficientTransmisijskihIzgub, 3) ?></td>
        <td class="w-10 center">
            <span>
            <b class="<?= $stavba->specKoeficientTransmisijskihIzgub < $stavba->dovoljenSpecKoeficientTransmisijskihIzgub ? 'green' : 'red' ?>">
            <?= $stavba->specKoeficientTransmisijskihIzgub < $stavba->dovoljenSpecKoeficientTransmisijskihIzgub ? '&#10003;' : '&#10006;' ?>
            </b></span>
        </td>
    </tr>
    <?php
        foreach ($cone as $i => $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-55">→ <?= h($cona->naziv) ?></td>
        <td class="w-10 center"><?= $this->numFormat($stavba->X_Htr)  ?></td>
        <td class="w-10 center"><?= $this->numFormat($cona->specKoeficientTransmisijskihIzgub, 3) ?></td>
        <td class="w-10 center"><?= $this->numFormat($cona->dovoljenSpecKoeficientTransmisijskihIzgub, 3) ?></td>
        <td class="w-10 center">
            <span>
            <b class="<?= $cona->specKoeficientTransmisijskihIzgub < $cona->dovoljenSpecKoeficientTransmisijskihIzgub ? 'green' : 'red' ?>">
            <?= $cona->specKoeficientTransmisijskihIzgub < $cona->dovoljenSpecKoeficientTransmisijskihIzgub ? '&#10003;' : '&#10006;' ?>
            </b></span>
        </td>
    </tr>
    <?php
        }
    ?>
    
</table>

<!-- ---------------------------------------------------------------------------- -->
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="5">Skupna prehodnost sončnega sevanja zasteklitve ali transparentnega dela ovoja g<sub>tot,sh</sub> s senčili g<sub>tot,s</sub> (-):</th>
    </tr>
    </thead>

    <tr>
        <td class="w-60" colspan="2">element</td>
        <td class="w-15 center">g<sub>tot</sub><br />(-)</td>
        <td class="w-15 center">g<sub>tot,sh</sub><br />(-)</td>
        <td class="w-10 center">Ustreza</td>
    </tr>

    <?php
        foreach ($cone as $cona) {
    ?>

    <?php
            $konstrukcije = array_combine(array_map(fn($k) => $k->id, $tKons), $tKons);
            foreach ($cona->ovoj->transparentneKonstrukcije as $i => $elementOvoja) {
                $konstrukcija = $konstrukcije[$elementOvoja->idKonstrukcije];
                if ($elementOvoja->delezOkvirja < 1) {
                    $brezZahtev = false;
                    if (in_array($elementOvoja->orientacija, ['SZ', 'S', 'SV']) && $elementOvoja->naklon > 65) {
                        $brezZahtev = true;
                    }
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-55"><?= h($konstrukcija->naziv) ?></td>
        <td class="w-15 center"><?= $this->numFormat($konstrukcija->g ?? 0, 2) ?></td>
        <td class="w-15 center"><?= $this->numFormat($elementOvoja->g_sh, 2) ?></td>
        <td class="w-10 center">
            <span title="g_tot,sh < 0,15"><?= $brezZahtev ? 'nz' : (
                '<b class=" ' . ($elementOvoja->g_sh > 0.15 ? 'red' : 'green') . '">' .
                ($elementOvoja->g_sh > 0.15 ? '&#10006;' : '&#10003;') .
                '</b>'
            ) ?></span>
        </td>
    </tr>
    <?php
                }
            }
        }
    ?>
</table>

<!-- ---------------------------------------------------------------------------- -->
<?php
    // TODO: Izpis faktorja dnevne svetlobe po conah
?>
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="4">Faktor dnevne svetlobe FDS (%):</th>
    </tr>
    </thead>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&#10003;</span></td>
        <td class="w-55">načrtovano</td>
        <td class="w-20 right">FDS<sub>T</sub> (%)</td>
        <td class="w-20 center"><?= $this->numFormat($cone[0]->razsvetljava->faktorDnevneSvetlobe * 100, 1) ?></td>
    </tr>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&nbsp;</span></td>
        <td colspan="3">izračunano</td>
    </tr>
</table>

<!-- ---------------------------------------------------------------------------- -->
<?php
    // TODO: Izpis n50 po conah
?>
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="6">Tesnost ovoja stavbe n<sub>50</sub> (h<sup>-1</sup>), w<sub>50</sub> (m³/(h m²)):</th>
    </tr>
    </thead>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&#10003;</span></td>
        <td class="w-55" colspan="3">načrtovano</td>
        <td class="w-10 right">n<sub>50</sub> (h<sup>-1</sup>)</td>
        <td class="w-10 center">0.5</td>
    </tr>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&nbsp;</span></td>
        <td colspan="5">izmerjeno</td>
    </tr>
    <tr>
        <td class="w-60" colspan="2">energetska cona oziroma stavba</td>
        <td class="w-10 center">n<sub>50</sub><br />(h<sup>-1</sup>)</td>
        <td class="w-10 center">ustreza</td>
        <td class="w-10 center">w<sub>50</sub><br />(m³/(h m²))</td>
        <td class="w-10 center">ustreza</td>
    </tr>
    <?php
        $i = 0;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-55"><?= h($cona->naziv) ?></td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
        <td class="w-10 center">&nbsp;</td>
    </tr>
    <?php
            $i++;
        }
    ?>
</table>


<!-- ---------------------------------------------------------------------------- -->
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="4">Koeficient transmisijskih toplotnih izgub konstrukcij v stiku z zemljino H<sub>gr,H</sub> in H<sub>gr,C</sub> (W/K):</th>
    </tr>
    </thead>

    <tr>
        <td class="w-60" colspan="2">konstrukcija</td>
        <td class="w-20 center">H<sub>gr,H</sub> (W/K)</td>
        <td class="w-20 center">H<sub>gr,C</sub> (W/K)</td>
    </tr>

    <?php
        foreach ($cone as $cona) {
    ?>

    <?php
            $konstrukcije = array_combine(array_map(fn($k) => $k->id, $ntKons), $ntKons);
            $i = 0;
            foreach (array_filter($cona->ovoj->netransparentneKonstrukcije, fn($k) => $k->protiZraku ? null : $k) as $elementOvoja) {
                $konstrukcija = $konstrukcije[$elementOvoja->idKonstrukcije];
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-55"><?= h($konstrukcija->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->H_ogrevanje, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->H_hlajenje, 2) ?></td>
    </tr>
    <?php
                $i++;
            }
        }
    ?>
</table>


<!-- ---------------------------------------------------------------------------- -->
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="4">Koeficient transmisijskih H'<sub>tr</sub> (W/K) in ventilacijskih H'<sub>ve</sub> (W/K) toplotnih izgub:</th>
    </tr>
    </thead>

    <tr>
        <td class="w-60" colspan="2">energetske cone oziroma stavba</td>
        <td class="w-20 center">H'<sub>tr</sub></td>
        <td class="w-20 center">H'<sub>ve</sub></td>
    </tr>
    <tr>
        <td class="w-5 center"></td>
        <td class="w-55">STAVBA</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specTransmisijskeIzgube, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specVentilacijskeIzgube, 2) ?></td>
    </tr>

    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-55">&rarr; <?= h($cona->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->specTransmisijskeIzgube, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->specVentilacijskeIzgube, 2) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>
</table>


<!-- ---------------------------------------------------------------------------- -->
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="4">Potrebna toplota za ogrevanje Q<sub>H,nd,an</sub> (kWh/an) in potrebna odvedena toplota za hlajenje Q<sub>C,nd,an</sub> (kWh/an):</th>
    </tr>
    </thead>

    <tr>
        <td class="w-60" colspan="2">energetske cone oziroma stavba</td>
        <td class="w-20 center">Q<sub>H,nd,an</sub><br />(kWh/an)</td>
        <td class="w-20 center">Q<sub>C,nd,an</sub><br />(kWh/an)</td>
    </tr>
    <tr>
        <td class="w-5 center"></td>
        <td class="w-55">STAVBA</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->skupnaEnergijaOgrevanje, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($stavba->skupnaEnergijaHlajenje, 2) ?></td>
    </tr>

    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-55">&rarr; <?= h($cona->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->skupnaEnergijaOgrevanje, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->skupnaEnergijaHlajenje, 2) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>
</table>


<!-- ---------------------------------------------------------------------------- -->
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr>
        <th colspan="4">Specifična potrebna toplota za ogrevanje Q'<sub>H,nd,an</sub> (kWh/(m²an)) in specifična potrebna odvedena toplota za hlajenje Q'<sub>C,nd,an</sub> (kWh/(m²an)):</th>
    </tr>
    </thead>

    <tr>
        <td class="w-60" colspan="2">energetske cone oziroma stavba</td>
        <td class="w-20 center">Q'<sub>H,nd,an</sub><br />(kWh/m²an)</td>
        <td class="w-20 center">Q'<sub>C,nd,an</sub><br />(kWh/m²an)</td>
    </tr>
    <tr>
        <td class="w-5 center"></td>
        <td class="w-55">STAVBA</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specLetnaToplota, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specLetniHlad, 2) ?></td>
    </tr>

    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-55">&rarr; <?= h($cona->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->specLetnaToplota, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->specLetniHlad, 2) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>

<!-- ---------------------------------------------------------------------------- -->
<?php
    if ($stavba->vrsta == 'zahtevna') {
?>
    <table border="1" cellpadding="3" width="100%">
        <tr>
            <td class="w-20">stavba</td>
            <td class="w-20 center">Q'<sub>H,nd,an</sub></td>
            <td class="w-20 center">Q'<sub>H,nd,an,ref</sub></td>
            <td class="w-20 center">Q'<sub>C,nd,an</sub></td>
            <td class="w-20 center">Q'<sub>C,nd,an,ref</sub></td>
        </tr>
        <tr>
            <td class="w-20 center">&nbsp;</td>
            <td class="w-20 center"><?= $this->numFormat($stavba->specLetnaToplota, 2) ?></td>
            <td class="w-20 center"><?= $this->numFormat($refStavba->specLetnaToplota, 2) ?></td>
            <td class="w-20 center"><?= $this->numFormat($stavba->specLetniHlad, 2) ?></td>
            <td class="w-20 center"><?= $this->numFormat($refStavba->specLetniHlad, 2) ?></td>
        </tr>
    </table>
    <table border="1" cellpadding="3" width="100%">
        <thead>
        <tr>
            <th colspan="6">Razmernik toplote za ogrevanje H<sub>nd (-)</sub> in razmernik odvedene toplote za hlajenje C<sub>nd</sub> (-) stavbe:</th>
        </tr>
        </thead>
        <tr>
            <td class="w-20 center">H<sub>nd</sub></td>
            <td class="w-20 center">H<sub>nd,dov</sub></td>
            <td class="w-10 center">ustreza</td>
            <td class="w-20 center">C<sub>nd</sub></td>
            <td class="w-20 center">C<sub>nd,dov</sub></td>
            <td class="w-10 center">ustreza</td>
        </tr>
        <tr>
            <td class="w-20 center"><?= $this->numFormat($stavba->specLetnaToplota / $refStavba->specLetnaToplota, 2) ?></td>
            <td class="w-20 center"><?= $this->numFormat($stavba->H_nd_dov, 2) ?></td>
            <td class="w-10 center"><?= $stavba->specLetnaToplota / $refStavba->specLetnaToplota < $stavba->H_nd_dov ? 'DA' : 'NE' ?></td>
            <td class="w-20 center"><?= $refStavba->specLetniHlad ? $this->numFormat($stavba->specLetniHlad / $refStavba->specLetniHlad, 2) : '-' ?></td>
            <td class="w-20 center"><?= $this->numFormat($stavba->C_nd_dov, 2) ?></td>
            <td class="w-10 center"><?= $refStavba->specLetniHlad ? ($stavba->specLetniHlad / $refStavba->specLetniHlad < $stavba->C_nd_dov ? 'DA' : 'NE') : '-' ?></td>
        </tr>
    </table>
<?php
    } else {
?>
    <table border="1" cellpadding="3" width="100%">
        <tr>
            <td class="w-20 center">X<sub>H,nd</sub></td>
            <td class="w-20 center">Q'<sub>H,nd,an</sub></td>
            <td class="w-20 center">Q'<sub>H,nd,an,dov</sub></td>
            <td class="w-20 center">ustreza</td>
            <td class="w-20 center">Y<sub>H,nd</sub></td>
        </tr>
        <tr>
            <td class="w-20 center"><?= $this->numFormat($stavba->X_Hnd, 1) ?></td>
            <td class="w-20 center"><?= $this->numFormat($stavba->specLetnaToplota, 2) ?></td>
            <td class="w-20 center"><?= $this->numFormat($stavba->dovoljenaSpecLetnaToplota, 2) ?></td>
            <td class="w-20 center"><?= $stavba->specLetnaToplota < $stavba->dovoljenaSpecLetnaToplota ? 'DA' : 'NE' ?></td>
            <td class="w-20 center"><?= $this->numFormat($stavba->Y_Hnd, 1) ?></td>
        </tr>
    </table>
<?php
    }
?>