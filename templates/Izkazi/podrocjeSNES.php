<h1>Energijska učinkovitost energetsko manj stavbe –<br />
za področje TSS</h1>

<h3>Potrebna energija za zagotavljanje pogojev notranjega okolja:</h3>
<table border="1" width="100%">
    <thead>
    <tr>
        <th colspan="4">Potrebna toplota za ogrevanje Q<sub>H,nd,an</sub> (kWh/an):</th>
    </tr>
    </thead>
    <tr>
        <td class="" colspan="3">sistem za ogrevanje – energetska cona ali stavba</td>
        <td class="w-20 center">QH,nd,an<br />(kWh/an)</td>
    </tr>
    <?php
        $i = 1;
        foreach ($cone as $cona) {
    ?>
    <tr>
        <td class="w-5 center"><?= $i ?></td>
        <td class="w-55" colspan="2"><?= h($cona->naziv) ?></td>
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
        <td class="w-20 right">Q'<sub>H,nd,an</sub> (kWh/m2 an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specLetnaToplota, 2) ?></td>
    </tr>


    <thead>
    <tr>
        <th colspan="4">Potrebna toplota za hlajenje Q<sub>C,nd,an</sub> (kWh/an):</th>
    </tr>
    </thead>
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
        <td class="w-55" colspan="2"><?= h($cona->naziv) ?></td>
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
        <td class="w-20 right">Q'<sub>C,nd,an</sub> (kWh/m2 an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specLetniHlad, 2) ?></td>
    </tr>


    <thead>
    <tr>
        <th colspan="4">Potrebna toplota za TSV Q<sub>W,nd,an</sub> (kWh/an):</th>
    </tr>
    </thead>
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
        <td class="w-55" colspan="2"><?= h($cona->naziv) ?></td>
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
        <td class="w-20 right">Q'<sub>W,nd,an</sub> (kWh/m2 an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specPotrebaTSV, 2) ?></td>
    </tr>


    <thead>
    <tr>
        <th colspan="4">Potrebna energija za navlaževanje zraka Q<sub>HU,nd,an</sub> (kWh/an):</th>
    </tr>
    </thead>
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
        <td class="w-55" colspan="2"><?= h($cona->naziv) ?></td>
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
        <td class="w-20 right">Q'<sub>HU,nd,an</sub> (kWh/m2 an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specEnergijaNavlazevanje, 2) ?></td>
    </tr>



    <thead>
    <tr>
        <th colspan="4">Potrebna energija za razvlaževanje zraka Q<sub>DHU,nd,an</sub> (kWh/an):</th>
    </tr>
    </thead>
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
        <td class="w-55" colspan="2"><?= h($cona->naziv) ?></td>
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
        <td class="w-20 right">Q'<sub>DHU,nd,an</sub> (kWh/m2 an)</td>
        <td class="w-20 center"><?= $this->numFormat($stavba->specEnergijaRazvlazevanje, 2) ?></td>
    </tr>
</table>


<!-- ---------------------------------------------------------------------------------------------------------------- -->
<h3>Dovedena energija za delovanje TSS:</h3>
<table border="1" width="100%">
    <thead>
    <?php
        
        $maxEnergentov = 0;
        foreach ($sistemiOgrevanja as $sistem) {
            if (count((array)$sistem->energijaPoEnergentih) > $maxEnergentov) {
                $maxEnergentov = count((array)$sistem->energijaPoEnergentih);
            }
        }
    ?>

    <tr>
        <th colspan="<?= (3 + $maxEnergentov) ?>">Dovedena energija za ogrevanje E<sub>H,del,an</sub> (kWh/an):</th>
    </tr>
    </thead>

    <tr>
        <td colspan="3"></td>
        <?php
            for ($i = 0; $i < $maxEnergentov; $i++) {
        ?>
        <td class="w-10 center">energent <?= ($i+1) ?></td>
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
        <td class="w-55" rowspan="2"><?= h($sistem->id) ?></td>
        <td class="w-10 center">vrsta</td>
        <?php
            foreach ($sistem->energijaPoEnergentih as $vrstaEnergenta => $energijaEnergenta) {
        ?>
            <td class="w-10 center"><?= h($vrstaEnergenta) ?></td>
        <?php
            }
        ?>
    </tr>

    <tr>
        <td class="w-10 center">količina</td>
        <?php
            foreach ($sistem->energijaPoEnergentih as $vrstaEnergenta => $energijaEnergenta) {
        ?>
            <td class="w-10 center"><?= $this->numFormat($energijaEnergenta, 2) ?></td>
        <?php
            }
        ?>
    </tr>
      
    <?php
            $i++;
        }
    ?>
</table>