<?php
    use App\Core\App;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<?php
    if ($stavba->vrsta == 'nezahtevna') {
?>
<h1>Kazalniki energijske učinkovitosti sNES</h1>
<p>Za energetsko nezahtevne stavbe se kazalniki energijske učinkovitosti sNES ne določajo.</p>
<?php
    } else {
        $kazalnik = new \stdClass();
        $kazalnik->specificna = $stavba->specificnaPrimarnaEnergija;
        $kazalnik->korigirana = $stavba->korigiranaSpecificnaPrimarnaEnergija;
        $kazalnik->rove = $stavba->ROVE;
        $kazalnik->co2 = $stavba->izpustCO2;

        if ($stavba->vrsta == 'zahtevna') {
            // Energetsko zahtevna stavba se primerja z referenčno stavbo
            $refKorigirana = $refStavba->korigiranaSpecificnaPrimarnaEnergija;
            $refKorigirano = abs($refKorigirana - $refStavba->specificnaPrimarnaEnergija) > 0.05;

            $kazalnik->meja = $refKorigirano ? $refKorigirana : $refStavba->specificnaPrimarnaEnergija;
            $kazalnik->mejaOznaka = $refKorigirano ? 'Ptot,ref,kor,an' : 'Ptot,ref,an';
            // Zeleno polje označuje tudi nižje izpuste od referenčne stavbe
            $kazalnik->co2Ustreza = $stavba->izpustCO2 < $refStavba->izpustCO2;
        } else {
            $kazalnik->meja = $stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija;
            $kazalnik->mejaOznaka = '';
            // Izpusti CO2 so pri manj zahtevni stavbi informativni kazalnik brez omejitev
            $kazalnik->co2Ustreza = false;
        }

        $kazalnik->energijaUstreza = $kazalnik->korigirana <= $kazalnik->meja;
        $kazalnik->roveUstreza = round($stavba->ROVE, 0) >= round($stavba->minROVE, 0);
?>
<h1>Kazalniki energijske učinkovitosti sNES "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>

<div class="kazalnikSNESOvoj">
    <?= $this->element('elements' . DS . 'kazalnikSNES', ['kazalnik' => $kazalnik]) ?>
</div>

<?= $this->element('elements' . DS . 'opozoriloROVE') ?>

<table border="1">
    <tr>
        <td colspan="3"><h2>Vrednosti na grafičnem prikazu</h2></td>
    </tr>
    <tr>
        <td>Specifična potrebna skupna primarna energija</td>
        <td>E'<sub>Ptot,an</sub></td>
        <td class="center"><?= $this->numFormat($kazalnik->specificna, 1) ?> kWh/(m<sup>2</sup>an)</td>
    </tr>
    <tr>
        <td>Korigirana specifična potrebna skupna primarna energija</td>
        <td>E'<sub>Ptot,kor,an</sub></td>
        <td class="center"><?= $this->numFormat($kazalnik->korigirana, 1) ?> kWh/(m<sup>2</sup>an)</td>
    </tr>
<?php
        if ($stavba->vrsta == 'zahtevna') {
?>
    <tr>
        <td>Specifična potrebna skupna primarna energija referenčne stavbe</td>
        <td>E'<sub><?= $kazalnik->mejaOznaka ?></sub></td>
        <td class="center"><?= $this->numFormat($kazalnik->meja, 1) ?> kWh/(m<sup>2</sup>an)</td>
    </tr>
<?php
        } else {
?>
    <tr>
        <td>Korigirana dovoljena specifična potrebna skupna primarna energija</td>
        <td>E'<sub>Ptot,kor,dov,an</sub></td>
        <td class="center"><?= $this->numFormat($kazalnik->meja, 1) ?> kWh/(m<sup>2</sup>an)</td>
    </tr>
<?php
        }
?>
    <tr>
        <td>Ustreza kriteriju sNES glede potrebne primarne energije</td>
        <td></td>
        <td class="center">
            <b class="<?= $kazalnik->energijaUstreza ? 'green' : 'red' ?>">
            <?= $kazalnik->energijaUstreza ? 'DA' : 'NE' ?>
            </b>
        </td>
    </tr>
    <tr>
        <td>Razmernik obnovljivih virov energije</td>
        <td>ROVE</td>
        <td class="center"><?= $this->numFormat($kazalnik->rove, 0) ?> %</td>
    </tr>
    <tr>
        <td>Minimalni zahtevani razmernik</td>
        <td>ROVE<sub>min</sub></td>
        <td class="center"><?= $this->numFormat($stavba->minROVE, 0) ?> %</td>
    </tr>
    <tr>
        <td>Ustreza minimalni zahtevi ROVE</td>
        <td></td>
        <td class="center">
            <b class="<?= $kazalnik->roveUstreza ? 'green' : 'red' ?>">
            <?= $kazalnik->roveUstreza ? 'DA' : 'NE' ?>
            </b>
        </td>
    </tr>
    <tr>
        <td>Izpusti ogljikovega dioksida</td>
        <td>M<sub>CO2,an</sub></td>
        <td class="center"><?= $this->numFormat($kazalnik->co2, 0) ?> kg/an</td>
    </tr>
</table>
<?php
    }
?>
