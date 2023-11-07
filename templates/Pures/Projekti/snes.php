<?php
    use App\Calc\GF\TSS\TSSVrstaEnergenta;
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Analiza sNES "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
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
    <?php
        $energije = [];
        $energijeFaktorjiTot = [];
        $energijeFaktorjiRen = [];
        $energijeFaktorjiNren = [];
        $energijeFaktorjiCO2 = [];
        foreach($stavba->sistemi as $i => $sistem) {
            foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                $energije[] = $this->numFormat($energija, 0, '.');
                $energijeFaktorjiTot[] = $this->numFormat($energija, 0, '.') . ' * ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('tot'), 2, '.');
                $energijeFaktorjiRen[] = $this->numFormat($energija, 0, '.') . ' * ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('ren'), 2, '.');
                $energijeFaktorjiNren[] = $this->numFormat($energija, 0, '.') . ' * ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('nren'), 2, '.');
                $energijeFaktorjiCO2[] = $this->numFormat($energija, 0, '.') . ' * ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2(), 2, '.');
            }
        };
    ?>
    <tr class="noprint">
        <td colspan="4" class="math">`E_(d el,an)=sum_(i=1)^n E_(de l,an,i)=<?= implode(' + ', $energije) ?> = <?= $this->numFormat($stavba->neutezenaDovedenaEnergija, 0) ?>`</td>
    </tr>
    <tr>
        <td>Utežena dovedena energija za delovanje TSS</td>
        <td>E<sub>w,del,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->utezenaDovedenaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`E_(w,d el,an)=sum_(i=1)^n E_(de l,an,i) * f_(P_"tot")=<?= implode(' + ', $energijeFaktorjiTot) ?>=<?= $this->numFormat($stavba->utezenaDovedenaEnergija, 0) ?>`</td>
    </tr>
    <tr>
        <td>Obnovljiva primarna energija dovedene energije</td>
        <td>E<sub>Pren,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->obnovljivaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`P_(ren,an)=sum_(i=1)^n E_(de l,an,i) * f_(P_"ren")=<?= implode(' + ', $energijeFaktorjiRen) ?>=<?= $this->numFormat($stavba->obnovljivaPrimarnaEnergija, 0) ?>`</td>
    </tr>
    <tr>
        <td>Neobnovljiva primarna energija dovedene energije</td>
        <td>E<sub>Pnren,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->neobnovljivaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`P_(nren,an)=sum_(i=1)^n E_(de l,an,i) * f_(P_"nren")=<?= implode(' + ', $energijeFaktorjiNren) ?>=<?= $this->numFormat($stavba->neobnovljivaPrimarnaEnergija, 0) ?>`</td>
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
    <tr class="noprint">
        <td colspan="4" class="math">`E'_(P_(t ot,an))=E_(P_(t ot,an))/A_(use)=<?= $this->numFormat($stavba->skupnaPrimarnaEnergija, 1, '.') ?>/<?= $this->numFormat($stavba->ogrevanaPovrsina, 1, '.') ?>=<?= $this->numFormat($stavba->specificnaPrimarnaEnergija, 3, '.') ?>`</td>
    </tr>
    <tr>
        <td>Korigirana specifična potrebna primarna energija</td>
        <td>E'<sub>Ptot,kor,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->korigiranaSpecificnaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`E'_(P_("tot,kor,an"))=Y_(H,nd) * Y_(ROVE) * E'_(P_(t ot,an))=<?= $this->numFormat($stavba->Y_Hnd, 1, '.') ?>*<?= $this->numFormat($stavba->Y_ROVE, 1, '.') ?>*<?= $this->numFormat($stavba->specificnaPrimarnaEnergija, 3, '.') ?>=<?= $this->numFormat($stavba->korigiranaSpecificnaPrimarnaEnergija, 3, '.') ?>`</td>
    </tr>
    <tr>
        <td>Dovoljena specifična potrebna skupna primarna energija</td>
        <td>E'<sub>Ptot,dov,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->dovoljenaSpecificnaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Korigirana dovoljena specifična potrebna skupna primarna energija</td>
        <td>E'<sub>Ptot,kor,dov,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`E'_(P_("tot,kor,dov,an"))=X_p * X_s * E'_(P_(t ot,dov,an))=<?= $this->numFormat($stavba->X_p, 1, '.') ?>*<?= $this->numFormat($stavba->X_s, 1, '.') ?>*<?= $this->numFormat($stavba->dovoljenaSpecificnaPrimarnaEnergija, 0, '.') ?>=<?= $this->numFormat($stavba->dovoljenaKorigiranaSpecificnaPrimarnaEnergija, 0, '.') ?>`</td>
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
    <tr class="noprint">
        <td colspan="4" class="math">`M_("CO2,an)=sum_(i=1)^n E_(de l,i,an) * k_(CO2,i) + sum_(j=1)^m E_(pr,on-site,j,an) * k_(CO2,j) - sum_(k=1)^l k_(exp) * E_(exp,k,an) * k_(CO2,exp,k)=<?= implode(' + ', $energijeFaktorjiCO2) ?>=<?= $this->numFormat($stavba->izpustCO2, 0) ?>`</td>
    </tr>
</table>