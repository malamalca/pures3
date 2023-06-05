<h1>Energijska učinkovitost energetsko manj stavbe –<br />
za področje TSS</h1>

<h3>Potrebna energija za zagotavljanje pogojev notranjega okolja:</h3>
<table border="1" cellpadding="3" width="100%">
    <tr>
        <th colspan="4">Potrebna toplota za ogrevanje Q<sub>H,nd,an</sub> (kWh/an):</th>
    </tr>
    <tr>
        <td class="w-80" colspan="3">sistem za ogrevanje – energetska cona ali stavba</td>
        <td class="w-20 center">QH,nd,an<br />(kWh/an)</td>
    </tr>
    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-75" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->skupnaEnergijaOgrevanje, 2) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>
    <tr>
        <td class="w-60" colspan="2"><b>SKUPAJ</b></td>
        <td class="w-20 right">Q<sub>H,nd,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->skupnaEnergijaOgrevanje, 2) ?></td>
    </tr>
    <tr>
        <td class="w-60" colspan="2">specifična potrebna toplota za ogrevanje stavbe</td>
        <td class="w-20 right">Q'<sub>H,nd,an</sub> (kWh/m² an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specLetnaToplota, 2) ?></td>
    </tr>


    <tr>
        <th colspan="4">Potrebna toplota za hlajenje Q<sub>C,nd,an</sub> (kWh/an):</th>
    </tr>
    <tr>
        <td class="" colspan="3">sistem za hlajenje – energetska cona ali stavba</td>
        <td class="w-20 center">Q<sub>C,nd,an</sub><br />(kWh/an)</td>
    </tr>
    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-75" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->skupnaEnergijaHlajenje, 2) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>
    <tr>
        <td class="w-60" colspan="2"><b>SKUPAJ</b></td>
        <td class="w-20 right">Q<sub>C,nd,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->skupnaEnergijaHlajenje, 2) ?></td>
    </tr>
    <tr>
        <td class="w-60" colspan="2">specifična potrebna toplota za hlajenje stavbe</td>
        <td class="w-20 right">Q'<sub>C,nd,an</sub> (kWh/m² an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specLetniHlad, 2) ?></td>
    </tr>


    <tr>
        <th colspan="4">Potrebna toplota za TSV Q<sub>W,nd,an</sub> (kWh/an):</th>
    </tr>
    <tr>
        <td class="" colspan="3">sistem za pripravo TSV – energetska cona ali stavba</td>
        <td class="w-20 center">Q<sub>W,nd,an</sub><br />(kWh/an)</td>
    </tr>
    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-75" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->skupnaPotrebaTSV, 2) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>
    <tr>
        <td class="w-60" colspan="2"><b>SKUPAJ</b></td>
        <td class="w-20 right">Q<sub>W,nd,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->skupnaPotrebaTSV, 2) ?></td>
    </tr>
    <tr>
        <td class="w-60" colspan="2">specifična potrebna toplota za pripravo TSV</td>
        <td class="w-20 right">Q'<sub>W,nd,an</sub> (kWh/m² an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specPotrebaTSV, 2) ?></td>
    </tr>


    <tr>
        <th colspan="4">Potrebna energija za navlaževanje zraka Q<sub>HU,nd,an</sub> (kWh/an):</th>
    </tr>
    <tr>
        <td class="" colspan="3">energetska cona ali stavba</td>
        <td class="w-20 center">Q<sub>HU,nd,an</sub><br />(kWh/an)</td>
    </tr>
    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-75" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->skupnaEnergijaNavlazevanje, 2) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>
    <tr>
        <td class="w-60" colspan="2"><b>SKUPAJ</b></td>
        <td class="w-20 right">Q<sub>HU,nd,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->skupnaEnergijaNavlazevanje, 2) ?></td>
    </tr>
    <tr>
        <td class="w-60" colspan="2">specifična potrebna energija za vlaženje zraka</td>
        <td class="w-20 right">Q'<sub>HU,nd,an</sub> (kWh/m² an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specEnergijaNavlazevanje, 2) ?></td>
    </tr>

    <tr>
        <th colspan="4">Potrebna energija za razvlaževanje zraka Q<sub>DHU,nd,an</sub> (kWh/an):</th>
    </tr>
    <tr>
        <td class="" colspan="3">energetska cona ali stavba</td>
        <td class="w-20 center">Q<sub>DHU,nd,an</sub><br />(kWh/an)</td>
    </tr>
    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-75" colspan="2"><?= h($cona->naziv) ?></td>
        <td class="w-20 center"><?= $this->numFormat($cona->skupnaEnergijaRazvlazevanje, 2) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>
    <tr>
        <td class="w-60" colspan="2"><b>SKUPAJ</b></td>
        <td class="w-20 right">Q<sub>DHU,nd,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->skupnaEnergijaRazvlazevanje, 2) ?></td>
    </tr>
    <tr>
        <td class="w-60" colspan="2">specifična potrebna energija za razvlaženje zraka</td>
        <td class="w-20 right">Q'<sub>DHU,nd,an</sub> (kWh/m² an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specEnergijaRazvlazevanje, 2) ?></td>
    </tr>
</table>


<!-- ---------------------------------------------------------------------------------------------------------------- -->
<h3>Dovedena energija za delovanje TSS:</h3>
<table border="1" cellpadding="3" width="100%">
    <?php
        $maxEnergentov = 0;
        foreach ($sistemiOgrevanja as $sistem) {
            if (count((array)$sistem->energijaPoEnergentih) > $maxEnergentov) {
                $maxEnergentov = count((array)$sistem->energijaPoEnergentih);
            }
        }
        $cellWidth = round(50/$maxEnergentov, 1);
    ?>
    <tr>
        <th colspan="<?= (3 + $maxEnergentov) ?>">Dovedena energija za ogrevanje E<sub>H,del,an</sub> (kWh/an):</th>
    </tr>

    <tr>
        <td class="w-50" colspan="3"></td>
        <?php
            for ($i = 0; $i < $maxEnergentov; $i++) {
        ?>
        <td class="center" style="width: <?= $cellWidth ?>%;">energent <?= ($i+1) ?></td>
        <?php 
            }
        ?>
    </tr>

    <?php
        $i = 1;
        foreach ($sistemiOgrevanja as $sistem) {
    ?>

    <tr>
        <td class="w-5 center" rowspan="2"><?= $i ?></td>
        <td class="w-35" rowspan="2"><?= h($sistem->id) ?></td>
        <td class="w-10 center">vrsta</td>
        <?php
            foreach ($sistem->energijaPoEnergentih as $vrstaEnergenta => $energijaEnergenta) {
        ?>
            <td class="w-<?= $cellWidth ?> center"><?= h($vrstaEnergenta) ?></td>
        <?php
            }
        ?>
    </tr>

    <tr>
        <td class="w-10 center">količina</td>
        <?php
            foreach ($sistem->energijaPoEnergentih as $vrstaEnergenta => $energijaEnergenta) {
        ?>
            <td class="w-<?= $cellWidth ?> center"><?= $this->numFormat($energijaEnergenta, 0) ?></td>
        <?php
            }
        ?>
    </tr>
      
    <?php
            $i++;
        }
    ?>
</table>






<table border="1" cellpadding="3" width="100%">
    <tr>
        <th colspan="4">Dovedena energija za mehansko prezračevanje E<sub>V,del,an</sub></th>
    </tr>
    <?php
        $i = 1;
        foreach ($sistemiPrezracevanja as $sistem) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-55"><?= h($sistem->id) ?></td>
        <td class="w-20 center"><?= h($sistem->idCone) ?></td>
        <td class="w-20 center"><?= $this->numFormat($sistem->skupnaDovodenaEnergija, 0) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>


    <tr>
        <th colspan="4">Dovedena energija za razsvetljavo E<sub>L,an</sub></th>
    </tr>
    <?php
        $i = 1;
        foreach ($sistemiRazsvetljave as $sistem) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-55"><?= h($sistem->id) ?></td>
        <td class="w-20 center"><?= h($sistem->idCone) ?></td>
        <td class="w-20 center"><?= $this->numFormat($sistem->skupnaDovodenaEnergija, 0) ?></td>
    </tr>
    <?php
                $i++;
        }
    ?>



    <tr>
        <th colspan="4">Dovedena energija za navlaževanje E<sub>HU,an</sub></th>
    </tr>

    <tr>
        <th colspan="4">Dovedena energija za navlaževanje E<sub>DHU,an</sub></th>
    </tr>

    <tr>
        <th colspan="3">Prilagojenost stavbe na pametne sisteme SRI (-):</th>
        <th class="w-20 center"></th>
    </tr>


    <tr>
        <th colspan="4">Oddani energent, proizveden v, na ob stavbi ali njeni neposredni bližini toplote Q<sub>exp</sub>, E<sub>exp,el</sub> (kWh/an)</th>
    </tr>
    <tr>
        <td colspan="3">oddana toplota, proizvedena v, na, ob stavbi ali njeni neposredni bližini Q<sub>exp,an</sub> (kWh/an)</td>
        <td></td>
    </tr>
    <tr>
        <td colspan="3">oddana električna energija, proizvedena v, na, ob stavbi ali njeni neposredni bližini E<sub>exp,el,an</sub> (kWh/an)</td>
        <td class="w-20 center"></td>
    </tr>
    <tr>
        <td colspan="3">faktor ujemanja f<sub>match,m</sub></td>
        <td class="w-20 center"></td>
    </tr>
    <tr>
        <td colspan="3">faktor k<sub>exp</sub></td>
        <td class="w-20 center"></td>
    </tr>
</table>



<h3>Kazalniki energijske učinkovitosti stavbe</h3>
<table border="1" cellpadding="3" width="100%">
    <tr>
        <td class="w-80" colspan="3">neutežena dovedena energija E<sub>del,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->neutezenaDovedenaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td colspan="3">utežena dovedena energija E<sub>w,del,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->utezenaDovedenaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td colspan="3">oddana toplota iz stavbe Q<sub>exp,an</sub> (kWh/an)</td>
        <td class="w-20 center">0</td>
    </tr>
    <tr>
        <td colspan="3">oddana električna energija iz stavbe E<sub>exp,el,an</sub> (kWh/an)</td>
        <td class="w-20 center">0</td>
    </tr>
    <tr>
        <td colspan="3">potrebna neobnovljiva primarna energija za delovanje TSS E<sub>Pnren,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->neobnovljivaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td colspan="3">potrebna obnovljiva primarna energija za delovanje TSS E<sub>Pren,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->obnovljivaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td colspan="3">potrebna skupna primarna energija za delovanje TSS E<sub>Ptot,an</sub> (kWh/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->utezenaDovedenaEnergija, 0) ?></td>
    </tr>

    <tr>
        <td colspan="3">specifična potrebna skupna primarna energija za delovanje TSS E'<sub>Ptot,an</sub> (kWh/m² an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specificnaPrimarnaEnergija, 1) ?></td>
    </tr>

    <tr>
        <td class="w-30">YH,nd (-)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->Y_Hnd, 1) ?></td>
        <td class="w-30">Yove (-)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->Y_ROVE, 1) ?></td>
    </tr>

    <tr>
        <td colspan="3">korigirana specifična potrebna skupna primarna energija za delovanje TSS E'<sub>Ptot,kor,an</sub> (kWh/m² an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->korigiranaSpecificnaPrimarnaEnergija, 1) ?></td>
    </tr>

    <tr>
        <td>Xp(-)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->X_p, 1) ?></td>
        <td class="w-30">Xs (-)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->X_s, 1) ?></td>
    </tr>

    <tr>
        <td colspan="3">dovoljena korigirana specifična potrebna skupna primarna energija za delovanje stavbe E'<sub>Ptot,kor,dov,an</sub> (kWh/m² an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija, 1) ?></td>
    </tr>

    <tr>
        <td colspan="3">ustreza (DA/NE)</td>
        <td class="w-20 center"><?= $stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija > $stavba->korigiranaSpecificnaPrimarnaEnergija ? 'DA' : 'NE' ?></td>
    </tr>


    <tr>
        <td colspan="3">ROVE v primarni energiji, potrebni za delovanje stavbe (%)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->ROVE, 1) ?></td>
    </tr>
    <tr>
        <td colspan="3">ROVE<sub>min</sub> (%)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->minROVE, 1) ?></td>
    </tr>
    <tr>
        <td colspan="3">ustreza (DA/NE)</td>
        <td class="w-20 center"><?= $stavba->ROVE > $stavba->minROVE ? 'DA' : 'NE' ?></td>
    </tr>

    <tr>
        <td colspan="3">izpusti CO<sub>2</sub> pri delovanju M<sub>CO2</sub> (kg/an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->izpustCO2, 0) ?></td>
    </tr>

</table>