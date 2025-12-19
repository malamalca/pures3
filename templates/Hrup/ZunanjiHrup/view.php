<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
    <a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>
<h1>Zunanji hrup</h1>
<table width="100%">
    <tr>
        <td colspan="2" class="w-30">Št.:</td>
        <td colspan="2" class="left strong"><?= h($prostor->id) ?></td>
    </tr>
    <tr>
        <td colspan="2" class="w-30">Naziv prostora:</td>
        <td colspan="2" class="left strong"><?= h($prostor->naziv) ?></td>
    </tr>
    <tr>
        <td class="w-20">Prostornina:</td>
        <td class="w-10 right strong">V=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->prostornina, 1) ?> m³</td>
    </tr>
    <tr>
        <td class="w-20">Površina ovoja:</td>
        <td class="w-10 right strong">S<sub>f</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->Sf, 1) ?> m²</td>
    </tr>
    <tr>
        <td class="w-20">Absorbcijska površina:</td>
        <td class="w-10 right strong">A<sub>f</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->Af, 1) ?> m²</td>
    </tr>
    <tr>
        <td class="w-20">Odmevni čas:</td>
        <td class="w-10 right strong">t<sub>0</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->odmevniCas, 1) ?> s</td>
    </tr>
    <tr>
        <td class="w-20">Nivo hrupa v prostoru:</td>
        <td class="w-10 right strong">L<sub>notri, max</sub>=</td>
        <td colspan="2" class="left strong"><?= h($prostor->Lmax) ?> dBA</td>
    </tr>
    <tr>
        <td class="w-20">Nivo zunanjega hrupa:</td>
        <td class="w-10 right strong">L<sub>zunaj, 2m</sub>=</td>
        <td colspan="2" class="left strong"><?= h($prostor->Lzunaj) ?> dBA</td>
    </tr>
</table>
<?php
    foreach ($prostor->fasade as $k => $fasada) {
?>
    
    <table width="100%">
    <tr class="title"><th colspan="4"><h2>Fasada <?= ($k+1) ?></h2></th></tr>
    <tr>
        <td class="w-20">Površina:</td>
        <td class="w-10 right strong">A=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($fasada->povrsina) ?> m²</td>
    </tr>
    <tr>
        <td class="w-20">Faktor oblike:</td>
        <td class="w-10 right strong">&Delta;L<sub>fs</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($fasada->deltaL_fasada, 0) ?> dB</td>
    </tr>
    <tr>
        <td class="w-30" colspan="2">Vpliv prometa:</td>
        <td colspan="2" class="left strong"><?= $fasada->vplivPrometa ? 'DA' : 'NE' ?></td>
    </tr>
    <tr>
        <td colspan="4" class="left">
            <table border="1" width="100%">
                <tr>
                    <th class="center strong">Z. št.</th>
                    <th class="left strong">Šifra</th>
                    <th class="left strong">Naziv konstrukcije</th>
                    <th class="center strong">Povr. masa<br/>[kg/m²]</th>
                    <th class="center strong">R<sub>w</sub> (C; C<sub>tr</sub>)</th>
                    <th class="center strong">R<sub>p,w</sub> (C; C<sub>tr</sub>)</th>
                    <th class="center strong">Št.</th>
                    <th class="center strong">Površina<br />[m²]</th>
                    <th class="center strong">Razmerje površin<br />S<sub>i</sub>/S<sub>f</sub> ali 10/S<sub>f</sub></th>
                    <th class="center strong">R<sub>p,w</sub>+C<?= $fasada->vplivPrometa ? '<sub>tr</sub>' : '' ?></th>
                </tr>
            <?php
                $k = 1;
                if (count($fasada->konstrukcije) > 0) {
                    foreach ($fasada->konstrukcije as $konstrukcija) {
                        $libKons = array_first($konstrukcije, fn($k) => $k->id == $konstrukcija->idKonstrukcije);
            ?>
                <tr>
                    <td class="center"><?= $k ?>.</td>
                    <td class="left"><?= h($libKons->id) ?></td>
                    <td class="left">
                        <?= h($libKons->naziv) ?>
                        <?php
                            if (!empty($libKons->dodatniSloji)) {
                                foreach ($libKons->dodatniSloji as $dodatniSloj) {
                        ?>
                            <div class="small">
                                + <?= h($dodatniSloj->naziv) ?>
                                (
                                    m'=<?= $this->numFormat($dodatniSloj->povrsinskaMasa, 1) ?> kg/m²
                                    <?php if ($dodatniSloj->vrsta == 'elasticen') echo ', s<sub>D</sub>=' . $this->numFormat($dodatniSloj->dinamicnaTogost, 1) . ' MN/m³'; ?>
                                    <?php if ($dodatniSloj->vrsta == 'nepritrjen') echo ', d=' . $this->numFormat($dodatniSloj->sirinaMedprostora, 1) . ' m'; ?>
                                )
                            </div>
                        <?php
                                }
                            }
                        ?>
                    </td>
                    <td class="center"><?= $this->numFormat($libKons->povrsinskaMasa, 1) ?> 
                    <td class="center">
                        <span clas="nowrap">
                        <?= $this->numFormat($libKons->Rw, 0) ?> 
                        (<?= $this->numFormat($libKons->C, 0) ?>; <?= $this->numFormat($libKons->Ctr, 0) ?>)
                        </span>
                        <?php
                            foreach ($libKons->dodatniSloji as $slojIx => $dodatniSloj) {
                        ?>
                            <div class="small">
                                &Delta;R<sub>sloj <?= ($slojIx+1) ?></sub> =<?= $this->numFormat($dodatniSloj->dR, 0) ?> dB
                            </div>
                        <?php
                            }
                        ?>
                    </td>
                    <td class="center nowrap">
                        <?= $this->numFormat($konstrukcija->Rw, 0) ?> 
                        (<?= $this->numFormat($konstrukcija->C, 0) ?>; <?= $this->numFormat($konstrukcija->Ctr, 0) ?>)
                    </td>
                    <td class="center"><?= $this->numFormat($konstrukcija->stevilo, 0) ?></td>
                    <td class="center"><?= $this->numFormat($konstrukcija->povrsina, 2) ?></td>
                    <td class="center"><?= $this->numFormat($konstrukcija->povrsina / $fasada->povrsina, 2) ?></td>
                    <td class="center"><?= $this->numFormat($konstrukcija->Rw + ($fasada->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C), 0) ?> dB
                        <br />
                        <?= sprintf('%.1E', $konstrukcija->povrsina * $konstrukcija->stevilo / $fasada->povrsina * pow(10, -($konstrukcija->Rw + ($fasada->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C)) / 10) * $konstrukcija->stevilo) ?>
                    </td>
                </tr>
            <?php
                        $k++;
                    }
                }
            ?>
            <?php
                if (count($fasada->oknaVrata) > 0) {
                    foreach ($fasada->oknaVrata as $konstrukcija) {
                        $libOknaVrata = array_first($oknaVrata, fn($k) => $k->id == $konstrukcija->idOknaVrata);
            ?>
                <tr>
                    <td class="center"><?= $k  ?>.</td>
                    <td class="left"><?= h($libOknaVrata->id) ?></td>
                    <td class="left"><?= h($libOknaVrata->naziv) ?></td>
                    <td class="left">&nbsp;</td>
                    <td class="center">
                        <span class="nowrap"><?= $this->numFormat($libOknaVrata->Rw, 0) ?> 
                        (<?= $this->numFormat($libOknaVrata->C, 0) ?>; <?= $this->numFormat($libOknaVrata->Ctr, 0) ?>)</span><br />
                        <div class="small">
                            &Delta;R<sub>TSG</sub> =<?= $this->numFormat($libOknaVrata->dR, 0) ?> dB
                        </div>
                    </td>
                    <td class="center nowrap">
                        <?= $this->numFormat($konstrukcija->Rw, 0) ?> 
                        (<?= $this->numFormat($konstrukcija->C, 0) ?>; <?= $this->numFormat($konstrukcija->Ctr, 0) ?>)
                    </td>
                    <td class="center"><?= $this->numFormat($konstrukcija->stevilo, 0) ?></td>
                    <td class="center"><?= $this->numFormat($konstrukcija->povrsina, 2) ?></td>
                    <td class="center"><?= $this->numFormat($konstrukcija->povrsina / $fasada->povrsina, 2) ?></td>
                    <td class="center"><?= $this->numFormat($konstrukcija->Rw + ($fasada->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C), 0) ?> dB
                        <br />
                        <?= sprintf('%.1E', $konstrukcija->povrsina * $konstrukcija->stevilo / $fasada->povrsina * pow(10, -($konstrukcija->Rw + ($fasada->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C)) / 10) * $konstrukcija->stevilo) ?>
                    </td>
                </tr>
            <?php
                        $k++;
                    }
                }
            ?>
            <?php
                if (count($fasada->maliElementi) > 0) {
                    foreach ($fasada->maliElementi as $konstrukcija) {
                        $libMaliElement = array_first($maliElementi, fn($k) => $k->id == $konstrukcija->idMaliElement);
            ?>
                <tr>
                    <td class="center"><?= $k  ?>.</td>
                    <td class="left"><?= h($libMaliElement->id) ?></td>
                    <td class="left"><?= h($libMaliElement->naziv) ?></td>
                    <td class="left">&nbsp;</td>
                    <td class="center">
                        <span class="nowrap"><?= $this->numFormat($libMaliElement->Rw, 0) ?> 
                        (<?= $this->numFormat($libMaliElement->C, 0) ?>; <?= $this->numFormat($libMaliElement->Ctr, 0) ?>)</span><br />
                        <?php
                            if (isset($konstrukcija->length)) {
                        ?>
                        <div class="small">
                            L =<?= $this->numFormat($konstrukcija->length, 2) ?> m
                        </div>
                        <?php
                            }
                        ?>
                    </td>
                    <td class="center nowrap">
                        <?= $this->numFormat($konstrukcija->Rw, 0) ?> 
                        (<?= $this->numFormat($konstrukcija->C, 0) ?>; <?= $this->numFormat($konstrukcija->Ctr, 0) ?>)
                    </td>
                    <td class="center"><?= $this->numFormat($konstrukcija->stevilo, 0) ?></td>
                    <td class="center">&nbsp;</td>
                    <td class="center">&nbsp;</td>
                    <td class="center"><?= $this->numFormat($konstrukcija->Rw + ($fasada->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C), 0) ?> dB
                        <br />
                        <?= sprintf('%.1E', 10 * $konstrukcija->stevilo / $fasada->povrsina * pow(10, -($konstrukcija->Rw + ($fasada->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C)) / 10) * $konstrukcija->stevilo) ?>
                    </td>
                </tr>
            <?php
                        $k++;
                    }
                }
            ?>
                <tr>
                    <th class="center">&nbsp;</th>
                    <th class="right strong" colspan="7">Skupaj:</th>
                    <th class="right strong">R<sub>w</sub> = </th>
                    <th class="center strong"><?= $this->numFormat($fasada->Rw, 1) ?> dB</th>
                </tr>
            <table>
        </td>
    </tr>
</table>
<?php
    }
?>

<table width="100%" border="1">
    <tr class="title"><th colspan="4"><h3>Ovoj prostora</h3></th></tr>
    <tr>
        <th class="strong">Z. št.:</th>
        <th class="center strong">Površina fasade<br />[m²]</th>
        <th class="center strong">Razmerje površin<br />S<sub>i</sub>/S<sub>f</sub></th>
        <th class="center strong">Ocenjena izolirnost<br />R<sub>w</sub></th>
    </tr>
<?php
    $i = 1;
    foreach ($prostor->fasade as $fasada) {
?>
    <tr>
        <td>Fasada <?= $i ?></td>
        <td class="center"><?= $this->numFormat($fasada->povrsina) ?></td>
        <td class="center"><?= $this->numFormat($fasada->povrsina / $prostor->Sf, 2) ?> 
        <td class="center"><?= $this->numFormat($fasada->Rw, 1) ?> dB</td>
    </tr>
<?php
        $i++;
    }
?>
    <tr>
        <th class="center">&nbsp;</th>
        <th class="right strong">Skupaj ovoj:</th>
        <th class="right strong">R<sub>w</sub> = </th>
        <th class="center strong"><?= $this->numFormat($prostor->Rw + 10 * log10($prostor->Sf / $prostor->Af) - $prostor->korekcijaBocnegaPrenosa, 1) ?> dB</th>
    </tr>
</table>

<table width="100%" border="1">
    <tr class="title"><th colspan="4"><h3>Skupaj za prostor</h3></th></tr>
    <tr>
        <td class="right strong" colspan="2">Izolirnost ovoja:</td>
        <td class="right strong">&nbsp;</td>
        <td class="center strong"><?= $this->numFormat($prostor->Rw + 10 * log10($prostor->Sf / $prostor->Af) - $prostor->korekcijaBocnegaPrenosa, 1) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong" colspan="2">Vpliv prostora:</td>
        <td class="right strong">&nbsp;</td>
        <td class="center strong">- <?= $this->numFormat(10 * log10($prostor->Sf / $prostor->Af), 1) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong" colspan="2">Korekcija bočnega prenosa:</td>
        <td class="right strong">&nbsp;</td>
        <td class="center strong"><?= $this->numFormat($prostor->korekcijaBocnegaPrenosa, 1) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong" colspan="2">Skupaj:</td>
        <td class="right strong">R'<sub>s,w</sub> = </td>
        <td class="center strong"><?= $this->numFormat($prostor->Rw, 0) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong" colspan="2">Min. zahteva:</td>
        <td class="right strong">R'<sub>min,w</sub> = </td>
        <td class="center strong"><?= $this->numFormat($prostor->minRw, 0) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong" colspan="3">USTREZNOST:</td>
        <td class="center strong <?= round($prostor->Rw, 0) >= round($prostor->minRw, 0) ? 'green' : 'red' ?>">
            <?= round($prostor->Rw, 0) >= round($prostor->minRw, 0) ? 'DA' : 'NE' ?>
        </td>
    </tr>
    <tr>
        <td class="right strong" colspan="2">Nivo hrupa v prostoru:</td>
        <td class="right strong">L<sub>notri</sub> = </td>
        <td class="center strong"><?= $this->numFormat($prostor->Lzunaj - $prostor->Rw, 0) ?> dBA</td>
    </tr>
</table>