<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Analiza sNES "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>
<p class="actions">
<a class="button" href="<?= App::url('/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<table border="1">
    <tr>
        <td colspan="4"><h2>Kazalniki energijske učinkovitosti stavbe</h2></td>
    </tr>
    <tr>
        <td colspan="2"></td>
        <td class="center">Količina (kWh/an)</td>
    </tr>
    <tr>
        <td>Neutežena dovedena energija za delovanje TSS</td>
        <td>E<sub>del,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->neutezenaDovedenaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Utežena dovedena energija za delovanje TSS</td>
        <td>E<sub>w,del,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->utezenaDovedenaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Obnovljiva primarna energija dovedene energije</td>
        <td>E<sub>Pren,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->obnovljivaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Neobnovljiva primarna energija dovedene energije</td>
        <td>E<sub>Pnren,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->neobnovljivaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Skupna primarna energija</td>
        <td>E<sub>Ptot,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaPrimarnaEnergija, 0) ?></td>
    </tr>

    <tr><td colspan="3"></td></tr>

    <tr>
        <td colspan="2"></td>
        <td class="center">Vrednost (%)</td>
    </tr>
    <tr>
        <td>Razmernik obnovljivih virov energije ROVE</td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->ROVE, 0) ?></td>
    </tr>
    <tr>
        <td>Minimalni zahtevani razmernik ROVE<sub>min</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->minROVE, 0) ?></td>
    </tr>
    <tr>
        <td>Ustreza minimalni zahtevi </td>
        <td></td>
        <td class="center">
            <b class="<?= $stavba->ROVE > $stavba->minROVE ? 'green' : 'red' ?>">
            <?= $stavba->ROVE > $stavba->minROVE ? 'DA' : 'NE' ?>
            </b>
        </td>
    </tr>


    <tr>
        <td colspan="2"></td>
        <td class="center">Vrednost (-)</td>
    </tr>
    <tr>
        <td>Korekcijski faktor razmernika ROVE X<sub>OVE</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->X_OVE, 1) ?></td>
    </tr>
    <tr>
        <td>Kompenzacijski faktor razmernika ROVE Y<sub>ROVE</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->Y_ROVE, 1) ?></td>
    </tr>

    <tr><td colspan="3"></td></tr>

    <tr>
        <td>Korekcijski faktor dovoljene skupne primarne energije glede na vrsto stavbe X<sub>s</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->X_s, 1) ?></td>
    </tr>
    <tr>
        <td>Korekcijski faktor dovoljene skupne primarne energije glede na leto uveljavitve X<sub>p</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->X_p, 1) ?></td>
    </tr>
    <tr>
        <td>Kompenzacijski faktor potrebne toplote za ogrevanje Y<sub>H,nd</sub></td>
        <td></td>
        <td class="center"><?= $this->numFormat($stavba->Y_Hnd, 1) ?></td>
    </tr>

    <tr><td colspan="3"></td></tr>

    <tr>
        <td colspan="2"></td>
        <td class="center">Količina (kWh/an)</td>
    </tr>
    <tr>
        <td>Specifična potrebna skupna primarna energija</td>
        <td>E'<sub>Ptot,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->specificnaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Korigirana specifična potrebna primarna energija</td>
        <td>E'<sub>Ptot,kor,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->korigiranaSpecificnaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Dovoljena specifična potrebna skupna primarna energija</td>
        <td>E'<sub>Ptot,kor,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->dovoljenaSpecificnaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Korigirana dovoljena specifična potrebna skupna primarna energija</td>
        <td>E'<sub>Ptot,kor,dov,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Ustreza minimalni zahtevi </td>
        <td></td>
        <td class="center">
            <b class="<?= $stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija > $stavba->korigiranaSpecificnaPrimarnaEnergija ? 'green' : 'red' ?>">
            <?= $stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija > $stavba->korigiranaSpecificnaPrimarnaEnergija ? 'DA' : 'NE' ?>
            </b>
        </td>
    </tr>


    <tr><td colspan="3"></td></tr>

    <tr>
        <td colspan="2"></td>
        <td class="center">Vrednost (kg/an)</td>
    </tr>
    <tr>
        <td>Izpusti ogljikovega dioksida</td>
        <td>M<sub>CO2,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->izpustCO2, 0) ?></td>
    </tr>
</table>