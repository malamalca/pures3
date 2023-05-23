<h1>Energijska učinkovitost energetsko manj stavbe –
za področje gradbene fizike</h1>

<h2>Kazalniki</h2>
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="4">Toplotna prehodnost gradbenih konstrukcij in gradnikov ovoja stavb U (W/(m<sup>2</sup> K)):</th>
    </tr>
    </thead>
    <?php
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-20 center">U<sub>op</sub>, U<sub>w</sub>, U<sub>d</sub><br />(W/(m<sup>2</sup> K))</td>
        <td class="w-5 center">Ustreza</td>
    </tr>
    <?php
            $konstrukcije = array_combine(array_map(fn($k) => $k->id, $ntKons), $ntKons);
            foreach ($cona->ovoj->netransparentneKonstrukcije as $i => $elementOvoja) {
                $konstrukcija = $konstrukcije[$elementOvoja->idKonstrukcije];
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-55"><?= h($konstrukcija->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->U, 2) ?></td>
        <td class="w-5 center">
            <span title="U < <?= $this->numFormat($konstrukcija->TSG->Umax, 2) ?>">
            <b class="<?= $elementOvoja->U < $konstrukcija->TSG->Umax ? 'green' : 'red' ?>">
            <?= $elementOvoja->U < $konstrukcija->TSG->Umax ? '&check;' : '&#10006;' ?>
            </b></span>
        </td>
    </tr>
    <?php
            }
            $konstrukcije = array_combine(array_map(fn($k) => $k->id, $tKons), $tKons);
            foreach ($cona->ovoj->transparentneKonstrukcije as $i => $elementOvoja) {
                $konstrukcija = $konstrukcije[$elementOvoja->idKonstrukcije];
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-55"><?= h($konstrukcija->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->U, 2) ?></td>
        <td class="w-5 center">
            <span title="U < <?= $this->numFormat($konstrukcija->TSG->Umax, 2) ?>">
            <b class="<?= $elementOvoja->U < $konstrukcija->TSG->Umax ? 'green' : 'red' ?>">
            <?= $elementOvoja->U < $konstrukcija->TSG->Umax ? '&check;' : '&#10006;' ?>
            </b></span>
        </td>
    </tr>
    <?php
            }
        }
    ?>
</table>

<!-- ---------------------------------------------------------------------------- -->
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="5">Linijske <i class="big serif">&Psi;</i> (W/(m K)) in točkovne <i class="big serif">&Chi;</i> (W/K) toplotne prehodnosti toplotnih mostov:</th>
    </tr>
    </thead>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&check;</span></td>
        <td colspan="1">Določitev po poenostavljeni metodi (s konstantno vrednostjo):</td>
        <td colspan="2" class="right">&Delta;<i class="big serif">&Psi;</i> (W/(m K))</td>
        <td class="w-20 center"><?= 5 ?></td>
    </tr>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&nbsp;</span></td>
        <td colspan="4">Natančnejši izračun</td>
    </tr>
</table>

<!-- ---------------------------------------------------------------------------- -->
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="6">Preverjanje prehoda vodne pare:</th>
    </tr>
    </thead>
    <?php
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-20 center">Kondenzacija se pojavi</td>
        <td class="w-20 center">Največja količina kondenzata</td>
        <td class="w-10 center">f<sub>Rsi</sub></td>
        <td class="w-5 center">Ustreza</td>
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
        <td class="w-20 center">
            <i class="<?= empty($elementOvoja->gm) ? 'green' : 'red' ?>">
            <?= empty($elementOvoja->gm) ? '&#10006;' : '&check;'  ?>
                </i>
        </td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->gm ?? 0, 1) ?></td>
        <td class="w-10 center"><?= $this->numFormat($konstrukcija->fRsi[0], 3) ?></td>
        <td class="w-5 center">
            <span title="fRsi > <?= $this->numFormat($okolje->limitfRsi, 3) ?>">
            <b class="<?= $elementOvoja->U < $konstrukcija->TSG->Umax ? 'green' : 'red' ?>">
                <?= $konstrukcija->fRsi[0] > $okolje->limitfRsi ? '&check;' : '&#10006;' ?>
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
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="7">Specifični koeficient transmisijskih toplotnih izgub H'tr (W/(m2 K)):</th>
    </tr>
    </thead>
    <tr>
        <td class="" colspan="2">Energetska cona oziroma stavba</td>
        <td class="w-10 center">XH'tr<br />(-)</td>
        <td class="w-10 center">H'tr<br />(W/(m2 K))</td>
        <td class="w-10 center">H'tr,dov<br />(W/(m2 K))</td>
        <td class="w-5 center">Ustreza</td>
    </tr>

    <tr>
        <td class="w-5 center"></td>
        <td class="">STAVBA</td>
        <td class="w-10 center"><?= $this->numFormat($stavba->X_Htr ?? 0, 1)  ?></td>
        <td class="w-10 center"><?= $this->numFormat($stavba->specKoeficientTransmisijskihIzgub, 3) ?></td>
        <td class="w-10 center"><?= $this->numFormat($stavba->dovoljenSpecKoeficientTransmisijskihIzgub, 3) ?></td>
        <td class="w-5 center">
            <span>
            <b class="<?= $stavba->specKoeficientTransmisijskihIzgub < $stavba->dovoljenSpecKoeficientTransmisijskihIzgub ? 'green' : 'red' ?>">
            <?= $stavba->specKoeficientTransmisijskihIzgub < $stavba->dovoljenSpecKoeficientTransmisijskihIzgub ? '&check;' : '&#10006;' ?>
            </b></span>
        </td>
    </tr>
    <?php
        foreach ($cone as $i => $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="">→ <?= h($cona->naziv) ?></td>
        <td class="w-10 center"><?= $this->numFormat($stavba->X_Htr ?? 0, 1)  ?></td>
        <td class="w-10 center"><?= $this->numFormat($cona->specKoeficientTransmisijskihIzgub, 3) ?></td>
        <td class="w-10 center"><?= $this->numFormat($cona->dovoljenSpecKoeficientTransmisijskihIzgub, 3) ?></td>
        <td class="w-5 center">
            <span>
            <b class="<?= $cona->specKoeficientTransmisijskihIzgub < $cona->dovoljenSpecKoeficientTransmisijskihIzgub ? 'green' : 'red' ?>">
            <?= $cona->specKoeficientTransmisijskihIzgub < $cona->dovoljenSpecKoeficientTransmisijskihIzgub ? '&check;' : '&#10006;' ?>
            </b></span>
        </td>
    </tr>
    <?php
        }
    ?>
    
</table>

<!-- ---------------------------------------------------------------------------- -->
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="5">Skupna prehodnost sončnega sevanja zasteklitve ali transparentnega dela ovoja g<sub>tot,sh</sub> s senčili g<sub>tot,s</sub> (-):</th>
    </tr>
    </thead>

    <tr>
        <td class="" colspan="2">element</td>
        <td class="w-20 center">g<sub>tot</sub><br />(-)</td>
        <td class="w-20 center">g<sub>tot,sh</sub><br />(-)</td>
        <td class="w-5 center">Ustreza</td>
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
        <td class="w-5 center"><?= $i+1 ?></td>
        <td class="w-55"><?= h($konstrukcija->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($konstrukcija->g ?? 0, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->g_sh, 2) ?></td>
        <td class="w-5 center">
            <span title="g_tot,sh < 0,15"><?= $brezZahtev ? 'nz' : (
                '<b class=" ' . ($elementOvoja->g_sh > 0.15 ? 'red' : 'green') . '">' .
                ($elementOvoja->g_sh > 0.15 ? '&#10006;' : '&check;') .
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
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="5">Faktor dnevne svetlobe FDS (%):</th>
    </tr>
    </thead>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&check;</span></td>
        <td colspan="1">načrtovano</td>
        <td colspan="2" class="right">FDS<sub>T</sub> (%)</td>
        <td class="w-20 center"><?= $this->numFormat($cone[0]->razsvetljava->faktorDnevneSvetlobe * 100, 1) ?></td>
    </tr>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&nbsp;</span></td>
        <td colspan="4">izračunano</td>
    </tr>
</table>

<!-- ---------------------------------------------------------------------------- -->
<?php
    // TODO: Izpis n50 po conah
?>
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="5">Tesnost ovoja stavbe n<sub>50</sub> (h<sup>-1</sup>), w<sub>50</sub> (m³/(h m²)):</th>
    </tr>
    </thead>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&check;</span></td>
        <td colspan="1">načrtovano</td>
        <td colspan="2" class="right">n<sub>50</sub> (h<sup>-1</sup>)</td>
        <td class="w-20 center">0.5</td>
    </tr>
    <tr>
        <td class="w-5 center"><span style="border: solid 1px black; display: inline-block; width: 18px;">&nbsp;</span></td>
        <td colspan="4">izračunano</td>
    </tr>
</table>


<!-- ---------------------------------------------------------------------------- -->
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="5">Koeficient transmisijskih toplotnih izgub konstrukcij v stiku z zemljino H<sub>gr,H</sub> in H<sub>gr,C</sub> (W/K):</th>
    </tr>
    </thead>

    <tr>
        <td class="" colspan="2">konstrukcija</td>
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
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->Hgr_ogrevanje, 2) ?></td>
        <td class="w-20 center"><?= $this->numFormat($elementOvoja->Hgr_hlajenje, 2) ?></td>
    </tr>
    <?php
                $i++;
            }
        }
    ?>
</table>


<!-- ---------------------------------------------------------------------------- -->
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="5">Koeficient transmisijskih H'tr (W/K) in ventilacijskih H've (W/K) toplotnih izgub:</th>
    </tr>
    </thead>

    <tr>
        <td class="" colspan="2">energetske cone oziroma stavba</td>
        <td class="w-20 center">H'tr</td>
        <td class="w-20 center">H've</td>
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
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="5">Potrebna toplota za ogrevanje QH,nd,an (kWh/an) in potrebna odvedena toplota za hlajenje QC,nd,an (kWh/an):</th>
    </tr>
    </thead>

    <tr>
        <td class="" colspan="2">energetske cone oziroma stavba</td>
        <td class="w-20 center">QH,nd,an<br />(kWh/an)</td>
        <td class="w-20 center">QC,nd,an<br />(kWh/an)</td>
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
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="5">Specifična potrebna toplota za ogrevanje Q'H,nd,an (kWh/(m2an)) in specifična potrebna odvedena toplota za hlajenje Q'C,nd,an (kWh/(m2an)):</th>
    </tr>
    </thead>

    <tr>
        <td class="" colspan="2">energetske cone oziroma stavba</td>
        <td class="w-20 center">Q'H,nd,an<br />(kWh/m2an)</td>
        <td class="w-20 center">Q'C,nd,an<br />(kWh/m2an)</td>
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
</table>