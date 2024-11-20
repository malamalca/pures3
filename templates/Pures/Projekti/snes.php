<?php
    use App\Calc\GF\TSS\TSSVrstaEnergenta;
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<?php
    if ($stavba->vrsta != 'nezahtevna') {
?>
<h1>Analiza sNES "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>
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
    <tr class="noprint">
        <td colspan="4" class="math">
            `E_(d el,an)=sum_(i=1)^n E_(de l,an,i)=`
            <?php
            $cards = [];
            foreach($stavba->sistemi as $i => $sistem) {
                $cardContents = '';
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    if ($energija > 0) {
                        if ($cardContents != '') {
                            $cardContents .= ' + ';
                        }
                        $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s</span></span>',
                            h($energent),
                            $this->numFormat($energija, 0, '.')
                        );
                    }
                }
                if ($cardContents != '') {
                    $card = '<span class="energyCard">';
                    $card .= sprintf('<span class="sistem sistem-%1$s">%2$s</span>', $sistem->tss, $sistem->id);
                    $card .= $cardContents;
                    $card .= '</span>';
                    $cards[] = $card;
                }
            }
            ?>
            <?= implode('+ ', $cards) ?> = <?= $this->numFormat($stavba->neutezenaDovedenaEnergija, 0) ?> kWh/an
        </td>
    </tr>
    <tr>
        <td>Utežena dovedena energija za delovanje TSS</td>
        <td>E<sub>w,del,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->utezenaDovedenaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">
            `E_(w,d el,an)=sum_(i=1)^n E_(de l,an,i) * f_(P_"tot")=`
            <?php
            $cards = [];
            foreach($stavba->sistemi as $i => $sistem) {
                $cardContents = '';
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    if ($energija > 0) {
                        if ($cardContents != '') {
                            $cardContents .= ' + ';
                        }
                        $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s</span></span>',
                            h($energent),
                            $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('tsg'), 2, '.')
                        );
                    }
                }
                if ($cardContents != '') {
                    $card = '<span class="energyCard">';
                    $card .= sprintf('<span class="sistem sistem-%1$s">%2$s</span>', $sistem->tss, $sistem->id);
                    $card .= $cardContents;
                    $card .= '</span>';
                    $cards[] = $card;
                }
            }
            ?>
            <?= implode('+ ', $cards) ?> = <?= $this->numFormat($stavba->utezenaDovedenaEnergija, 0) ?> kWh/an
        </td>
    </tr>
    <tr>
        <td>Obnovljiva primarna energija dovedene energije</td>
        <td>E<sub>Pren,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->obnovljivaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">
            `E_(P_(ren,an))=sum_(i=1)^n E_(de l,an,i) * f_(P_"ren")=`
            <?php
            $cards = [];
            foreach($stavba->sistemi as $i => $sistem) {
                $cardContents = '';
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    if ($cardContents != '') {
                        $cardContents .= ' + ';
                    }
                    $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s</span></span>',
                        h($energent),
                        $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('ren'), 2, '.')
                    );
                }
                if (isset($sistem->proizvedenaEnergijaPoEnergentih)) {
                    foreach ((array)$sistem->proizvedenaEnergijaPoEnergentih as $energent => $energija) {
                        if ($cardContents != '') {
                            $cardContents .= ' + ';
                        }
                        $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s (+)</span></span>',
                            h($energent),
                            $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('ren'), 2, '.')
                        );
                    }
                }
                if ($cardContents != '') {
                    $card = '<span class="energyCard">';
                    $card .= sprintf('<span class="sistem sistem-%1$s">%2$s</span>', $sistem->tss, $sistem->id);
                    $card .= $cardContents;
                    $card .= '</span>';
                    $cards[] = $card;
                }
            }
            ?>
            <?= implode('+ ', $cards) ?> = <?= $this->numFormat($stavba->obnovljivaPrimarnaEnergija, 0) ?> kWh/an
        </td>
    </tr>
    <tr>
        <td>Neobnovljiva primarna energija dovedene energije</td>
        <td>E<sub>Pnren,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->neobnovljivaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">
            `E_(P_(nren,an))=sum_(i=1)^n E_(de l,an,i) * f_(P_"nren")=`
            <?php
            $cards = [];
            foreach($stavba->sistemi as $i => $sistem) {
                $cardContents = '';
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    if ($cardContents != '') {
                        $cardContents .= ' + ';
                    }
                    $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s</span></span>',
                        h($energent),
                        $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('nren'), 2, '.')
                    );
                }
                if (isset($sistem->proizvedenaEnergijaPoEnergentih)) {
                    foreach ((array)$sistem->proizvedenaEnergijaPoEnergentih as $energent => $energija) {
                        if ($cardContents != '') {
                            $cardContents .= ' + ';
                        }
                        $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s (+)</span></span>',
                            h($energent),
                            $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('nren'), 2, '.')
                        );
                    }
                }
                if ($cardContents != '') {
                    $card = '<span class="energyCard">';
                    $card .= sprintf('<span class="sistem sistem-%1$s">%2$s</span>', $sistem->tss, $sistem->id);
                    $card .= $cardContents;
                    $card .= '</span>';
                    $cards[] = $card;
                }
            }
            ?>
            <?= implode('+ ', $cards) ?> = <?= $this->numFormat($stavba->neobnovljivaPrimarnaEnergija, 0) ?> kWh/an
        </td>
    </tr>
    <tr>
        <td>Skupna primarna energija</td>
        <td>E<sub>Ptot,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaPrimarnaEnergija, 0) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">
            `E_(P_(t ot,an))=sum_(i=1)^n E_(de l,an,i) * f_(P_"tot")=`
            <?php
            $cards = [];
            foreach($stavba->sistemi as $i => $sistem) {
                $cardContents = '';
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    if ($cardContents != '') {
                        $cardContents .= ' + ';
                    }
                    $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s</span></span>',
                        h($energent),
                        $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('tot'), 2, '.')
                    );
                }
                if (isset($sistem->proizvedenaEnergijaPoEnergentih)) {
                    foreach ((array)$sistem->proizvedenaEnergijaPoEnergentih as $energent => $energija) {
                        if ($cardContents != '') {
                            $cardContents .= ' + ';
                        }
                        $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s (+)</span></span>',
                            h($energent),
                            $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('tot'), 2, '.')
                        );
                    }
                }
                if (isset($sistem->oddanaEnergijaPoEnergentih)) {
                    foreach ((array)$sistem->oddanaEnergijaPoEnergentih as $energent => $energija) {
                        if ($cardContents != '') {
                            $cardContents .= ' - ';
                        }
                        $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s (odd.)</span></span>',
                            h($energent),
                            $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat($stavba->k_exp, 2, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->utezniFaktor('tot'), 2, '.')
                        );
                    }
                }
                if ($cardContents != '') {
                    $card = '<span class="energyCard">';
                    $card .= sprintf('<span class="sistem sistem-%1$s">%2$s</span>', $sistem->tss, $sistem->id);
                    $card .= $cardContents;
                    $card .= '</span>';
                    $cards[] = $card;
                }
            }
            ?>
            <?= implode('+ ', $cards) ?> = <?= $this->numFormat($stavba->skupnaPrimarnaEnergija, 0) ?> kWh/an
        </td>
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
            <b class="<?= round($stavba->ROVE, 0) >= round($stavba->minROVE, 0) ? 'green' : 'red' ?>">
            <?= round($stavba->ROVE, 0) >= round($stavba->minROVE, 0) ? 'DA' : 'NE' ?>
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
        <td class="center"><?= $this->numFormat($stavba->specificnaPrimarnaEnergija, 1) ?></td>
    </tr>
    <tr class="noprint">
        <td colspan="4" class="math">`E'_(P_(t ot,an))=E_(P_(t ot,an))/A_(use)=<?= $this->numFormat($stavba->skupnaPrimarnaEnergija, 1, '.') ?>/<?= $this->numFormat($stavba->ogrevanaPovrsina, 1, '.') ?>=<?= $this->numFormat($stavba->specificnaPrimarnaEnergija, 3, '.') ?>`</td>
    </tr>
    <tr>
        <td>Korigirana specifična potrebna primarna energija</td>
        <td>E'<sub>Ptot,kor,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->korigiranaSpecificnaPrimarnaEnergija, 1) ?></td>
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
        <td colspan="4" class="math">
            `M_("CO2,an)=sum_(i=1)^n E_(de l,i,an) * k_(CO2,i) + sum_(j=1)^m E_(pr,on-site,j,an) * k_(CO2,j) - sum_(k=1)^l k_(exp) * E_(exp,k,an) * k_(CO2,exp,k)=`<br />
            <?php
            $cards = [];
            foreach($stavba->sistemi as $i => $sistem) {
                $cardContents = '';
                foreach ($sistem->energijaPoEnergentih as $energent => $energija) {
                    if ($cardContents != '') {
                        $cardContents .= ' + ';
                    }
                    $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s</span></span>',
                        h($energent),
                        $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2(), 2, '.')
                    );
                }
                if (isset($sistem->proizvedenaEnergijaPoEnergentih)) {
                    foreach ((array)$sistem->proizvedenaEnergijaPoEnergentih as $energent => $energija) {
                        if ($cardContents != '') {
                            $cardContents .= ' + ';
                        }
                        $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s (+)</span></span>',
                            h($energent),
                            $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2(), 2, '.')
                        );
                    }
                }
                if (isset($sistem->oddanaEnergijaPoEnergentih)) {
                    foreach ((array)$sistem->oddanaEnergijaPoEnergentih as $energent => $energija) {
                        if ($cardContents != '') {
                            $cardContents .= ' - ';
                        }
                        $cardContents .= sprintf('<span class="energent energent-%1$s">%2$s<span class="energent-title">%1$s (odd.)</span></span>',
                            h($energent),
                            $this->numFormat($energija, 0, '.') . ' x ' . $this->numFormat(TSSVrstaEnergenta::from($energent)->faktorIzpustaCO2(), 2, '.')
                        );
                    }
                }
                if ($cardContents != '') {
                    $card = '<span class="energyCard">';
                    $card .= sprintf('<span class="sistem sistem-%1$s">%2$s</span>', $sistem->tss, $sistem->id);
                    $card .= $cardContents;
                    $card .= '</span>';
                    $cards[] = $card;
                }
            }
            ?>
            <?= implode('+ ', $cards) ?> = <?= $this->numFormat($stavba->izpustCO2, 0) ?> kg/an
        </td>
    </tr>
</table>
<br />
<table border="1">
    <tr>
        <td colspan="4"><h2>V/na/ob stavbi proizveden energent in energent oddan v omrežje</h2></td>
    </tr>
    <tr>
        <td colspan="2"></td>
        <td class="center">Količina (kWh/an)</td>
    </tr>
    <tr>
        <td>Proizvedena električna energija</td>
        <td>E<sub>PV,pr,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaProizvedenaElektricnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Proizvedena električna energija porabljena na stavbi</td>
        <td>E<sub>PV,used,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaProizvedenaPorabljenaElektricnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Oddana elekrična energija iz stavbe</td>
        <td>E<sub>PV,exp,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->skupnaOddanaElektricnaEnergija, 0) ?></td>
    </tr>
    <tr>
        <td>Faktor ujemanja na stavbi proizvedene in porabljene električne energije</td>
        <td>f<sub>match,avg,an</sub></td>
        <td class="center"><?= $this->numFormat($stavba->faktorUjemanja, 2) ?></td>
    </tr>
    <tr>
        <td>Kontrolni faktor oddane električne energije</td>
        <td>k<sub>exp</sub></td>
        <td class="center"><?= $this->numFormat($stavba->k_exp, 1) ?></td>
    </tr>
</table>
<?php
    }
?>