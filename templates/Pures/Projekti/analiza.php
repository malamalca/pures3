<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<?php
    if ($stavba->vrsta != 'nezahtevna') {
?>
<h1>Analiza Projekta "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>
<table border="1">
    <tr>
        <td>Bruto ogrevana prostornina stavbe</td>
        <td>V<sub>e</sub></td>
        <td class="center"><?= $this->numFormat($stavba->brutoProstornina, 1) ?></td>
        <td>m³</td>
    </tr>
    <tr>
        <td>Površina toplotnega ovoja stavbe</td>
        <td>A<sub>ovoj</sub></td>
        <td class="center"><?= $this->numFormat($stavba->povrsinaOvoja, 1) ?></td>
        <td>m²</td>
    </tr>
    <tr>
        <td>Kondicionirana površina stavbe</td>
        <td>A<sub>use</sub></td>
        <td class="center"><?= $this->numFormat($stavba->ogrevanaPovrsina, 1) ?></td>
        <td>m²</td>
    </tr>
    <tr>
        <td>Transp. površina v toplotnem ovoju stavbe</td>
        <td>A<sub>trans</sub></td>
        <td class="center"><?= $this->numFormat($stavba->transparentnaPovrsina, 2) ?></td>
        <td>m²</td>
    </tr>
    <tr>
        <td>Faktor oblike stavbe</td>
        <td>f<sub>0</sub></td>
        <td class="center"><?= $this->numFormat($stavba->faktorOblike, 3) ?></td>
        <td>m<sup>-1</sup></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`f_0=A_(ovoj)/V_e=<?= $this->numFormat($stavba->povrsinaOvoja, 1, '.') ?>/<?= $this->numFormat($stavba->brutoProstornina, 1, '.') ?>=<?= $this->numFormat($stavba->faktorOblike, 3, '.') ?>`</td>
    </tr>
    <tr>
        <td>Razmerje transp./celotne površine ovoja</td>
        <td>z</td>
        <td class="center"><?= $this->numFormat($stavba->razmerjeTranspCelota, 3) ?></td>
        <td>-</td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`z=A_(trans)/A_(ovoj)=<?= $this->numFormat($stavba->transparentnaPovrsina, 1, '.') ?>/<?= $this->numFormat($stavba->povrsinaOvoja, 1, '.') ?>=<?= $this->numFormat($stavba->razmerjeTranspCelota, 3, '.') ?>`</td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>

    <tr>
        <td>Spec. koef. transm. topl. izgub</td>
        <td>H'<sub>tr</sub></td>
        <td class="center"><?= $this->numFormat($stavba->specKoeficientTransmisijskihIzgub, 3) ?></td>
        <td>W/m²K</td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`H'_(tr)=H_(tr)/A_(ovoj)=<?= $this->numFormat($stavba->specTransmisijskeIzgube, 1, '.') ?>/<?= $this->numFormat($stavba->povrsinaOvoja, 1, '.') ?>=<?= $this->numFormat($stavba->specKoeficientTransmisijskihIzgub, 3, '.') ?>`</td>
    </tr>
    <tr>
        <td class="right">X<sub>H'tr</sub> × H'<sub>tr,dov</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->dovoljenSpecKoeficientTransmisijskihIzgub * $stavba->X_Htr, 3) ?></td>
        <td>W/m²K</td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">
            `H'_(tr,dov)=0.25 + theta_(an)/300 + 0.04/f_0 + z/8 = 0.25 + <?= $this->numFormat($stavba->povprecnaLetnaTemp, 1, '.') ?>/300 + 0.04/<?= $this->numFormat($stavba->faktorOblike, 3, '.') ?> + <?= $this->numFormat($stavba->razmerjeTranspCelota, 3, '.') ?>/8 = <?= $this->numFormat($stavba->dovoljenSpecKoeficientTransmisijskihIzgub, 3, '.') ?>`<br />
            `X_(H'_(tr)) * H'_(tr,dov)=<?= $this->numFormat($stavba->dovoljenSpecKoeficientTransmisijskihIzgub, 3, '.') ?>*<?= $this->numFormat($stavba->X_Htr, 1, '.') ?>=<?= $this->numFormat($stavba->dovoljenSpecKoeficientTransmisijskihIzgub * $stavba->X_Htr, 3, '.') ?>`
        </td>
    </tr>
    <tr>
        <td class="right">X<sub>H'tr</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->X_Htr, 1) ?></td>
        <td>-</td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>

    <tr>
        <td>Potrebna toplota za ogrevanje stavbe</td>
        <td>Q<sub>H,nd,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaEnergijaOgrevanje, 0) ?></td>
        <td>kWh/an</td>
    </tr>
    <tr>
        <td>Potrebna toplota za hlajenje stavbe</td>
        <td>Q<sub>C,nd,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaEnergijaHlajenje, 0) ?></td>
        <td>kWh/an</td>
    </tr>
    <tr>
        <td>Potrebna toplota za pripravo TSV</td>
        <td>Q<sub>W,nd,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaEnergijaTSV, 0) ?></td>
        <td>kWh/an</td>
    </tr>
    <tr>
        <td>Potrebna energija za vlaženje zraka</td>
        <td>Q<sub>HU,nd,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaEnergijaNavlazevanje, 0) ?></td>
        <td>kWh/an</td>
    </tr>
    <tr>
        <td>Potrebna energija za razvlaževanje zraka</td>
        <td>Q<sub>DHU,nd,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaEnergijaRazvlazevanje, 0) ?></td>
        <td>kWh/an</td>
    </tr>
    <tr>
        <td>Dovedena energija za razsvetljavo</td>
        <td>E<sub>L,del,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaEnergijaRazsvetljava, 0) ?></td>
        <td>kWh/an</td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>

    <tr>
        <td>Specifična potrebna toplota za ogrevanje</td>
        <td>Q'<sub>H,nd,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->specLetnaToplota, 1) ?></td>
        <td>kWh/m²an</td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`Q'_(H,nd,an)=Q_(H,nd,an)/A_(use)=<?= $this->numFormat($stavba->skupnaEnergijaOgrevanje, 1, '.') ?>/<?= $this->numFormat($stavba->ogrevanaPovrsina, 1, '.') ?>=<?= $this->numFormat($stavba->specLetnaToplota, 3, '.') ?>`</td>
    </tr>
    <tr>
        <td class="right">X<sub>H,nd</sub> × Q'<sub>H,nd,dov,an</sub></sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->dovoljenaSpecLetnaToplota, 1) ?></td>
        <td>kWh/m²an</td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`Q'_(H,nd,dov,an)= 25 (kWh)/(m^2an)`</td>
    </tr>
    <tr>
        <td class="right">X<sub>H,nd</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->X_Hnd, 1) ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td class="right">Y<sub>H,nd</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->Y_Hnd, 1) ?></td>
        <td>-</td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>

    <tr>
        <td>Spec. potr. odvedena toplota za hlajenje</td>
        <td>Q'<sub>C,nd,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->specLetniHlad, 1) ?></td>
        <td>kWh/m²an</td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`Q'_(C,nd,an)=Q_(C,nd,an)/A_(use)=<?= $this->numFormat($stavba->skupnaEnergijaHlajenje, 1, '.') ?>/<?= $this->numFormat($stavba->ogrevanaPovrsina, 1, '.') ?>=<?= $this->numFormat($stavba->specLetniHlad, 3, '.') ?>`</td>
    </tr>
</table>
<?php
    }
?>