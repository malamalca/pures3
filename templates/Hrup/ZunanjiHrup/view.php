<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Zunanji hrup</h1>

<p class="actions">
    <a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

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
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->prostornina, 1) ?> m3</td>
    </tr>
    <tr>
        <td class="w-20">Površina ovoja:</td>
        <td class="w-10 right strong">S<sub>f</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->Sf, 1) ?> m2</td>
    </tr>
    <tr>
        <td class="w-20">Absorbcijska površina:</td>
        <td class="w-10 right strong">A<sub>f</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->Af, 1) ?> m2</td>
    </tr>
    <tr>
        <td class="w-20">Odmevni čas:</td>
        <td class="w-10 right strong">t<sub>0</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->odmevniCas, 1) ?> s</td>
    </tr>
    <tr>
        <td class="w-20">Nivo hrupa v prostoru:</td>
        <td class="w-10 right strong">L<sub>notri</sub>=</td>
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
    <h3>Fasada <?= ($k+1) ?></h3>
    <table width="100%">
    <tr>
        <td class="w-20">Površina:</td>
        <td class="w-10 right strong">A=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($fasada->povrsina) ?> m2</td>
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
                    <td class="center">Z. št.</td>
                    <td class="left">Šifra</td>
                    <td class="left">Naziv konstrukcije</td>
                    <td class="center">Povr. masa<br/>[kg/m2]</td>
                    <td class="center">R<sub>w</sub> (C; C<sub>tr</sub>)</td>
                    <td class="center">R<sub>p,w</sub> (C; C<sub>tr</sub>)</td>
                    <td class="center">Št.</td>
                    <td class="center">Površina<br />[m<sup>2</sup>]</td>
                    <td class="center">Razmerje površin<br />S<sub>i</sub>/S<sub>f</sub> ali 10/S<sub>f</sub></td>
                    <td class="center">R<sub>p,w</sub>+C<?= $fasada->vplivPrometa ? '<sub>tr</sub>' : '' ?></td>
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
                                    m'=<?= $this->numFormat($dodatniSloj->povrsinskaMasa, 1) ?> kg/m2
                                    <?php if ($dodatniSloj->vrsta == 'elasticen') echo ', s<sub>D</sub>=' . $this->numFormat($dodatniSloj->dinamicnaTogost, 1) . ' MN/m<sup>3</sup>'; ?>
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
                        <?= $this->numFormat($libKons->Rw, 1) ?> 
                        (<?= $this->numFormat($libKons->C, 0) ?>; <?= $this->numFormat($libKons->Ctr, 0) ?>)
                        <?php
                            foreach ($libKons->dodatniSloji as $slojIx => $dodatniSloj) {
                        ?>
                            <div class="small">
                                &Delta;R<sub>sloj <?= ($slojIx+1) ?></sub> =<?= $this->numFormat($dodatniSloj->dR, 1) ?> dB
                            </div>
                        <?php
                            }
                        ?>
                    </td>
                    <td class="center">
                        <?= $this->numFormat($konstrukcija->Rw, 1) ?> 
                        (<?= $this->numFormat($konstrukcija->C, 0) ?>; <?= $this->numFormat($konstrukcija->Ctr, 0) ?>)
                    </td>
                    <td class="center"><?= $this->numFormat($konstrukcija->stevilo, 0) ?> 
                    <td class="center"><?= $this->numFormat($konstrukcija->povrsina, 1) ?> 
                    <td class="center"><?= $this->numFormat($konstrukcija->povrsina / $fasada->povrsina, 2) ?> 
                    <td class="center"><?= $this->numFormat($konstrukcija->Rw + ($fasada->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C), 1) ?> dB</td>
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
                        <?= $this->numFormat($libOknaVrata->Rw, 1) ?> 
                        (<?= $this->numFormat($libOknaVrata->C, 0) ?>; <?= $this->numFormat($libOknaVrata->Ctr, 0) ?>)<br />
                        <div class="small">
                            &Delta;R<sub>TSG</sub> =<?= $this->numFormat($libOknaVrata->dR, 1) ?> dB
                        </div>
                    </td>
                    <td class="center">
                        <?= $this->numFormat($konstrukcija->Rw, 1) ?> 
                        (<?= $this->numFormat($konstrukcija->C, 0) ?>; <?= $this->numFormat($konstrukcija->Ctr, 0) ?>)
                    </td>
                    <td class="center"><?= $this->numFormat($konstrukcija->stevilo, 0) ?> 
                    <td class="center"><?= $this->numFormat($konstrukcija->povrsina, 1) ?> 
                    <td class="center"><?= $this->numFormat($konstrukcija->povrsina / $fasada->povrsina, 2) ?> 
                    <td class="center"><?= $this->numFormat($konstrukcija->Rw + ($fasada->vplivPrometa ? $konstrukcija->Ctr : $konstrukcija->C), 1) ?> dB</td>
                </tr>
            <?php
                        $k++;
                    }
                }
            ?>
                <tr>
                    <td class="center">&nbsp;</td>
                    <td class="right strong" colspan="7">Skupaj:</td>
                    <td class="right strong">R'<sub>w</sub> = </td>
                    <td class="center strong"><?= $this->numFormat($fasada->Rw, 1) ?> dB</td>
                </tr>
            <table>
        </td>
    </tr>
</table>
<?php
    }
?>

<h3>Skupaj za prostor</h3>
<table width="100%" border="1">
    <tr>
        <td class="strong">Z. št.:</td>
        <td class="center strong">Površina fasade<br />[m<sup>2</sup>]</td>
        <td class="center strong">Razmerje površin<br />S<sub>i</sub>/S<sub>f</sub></td>
        <td class="center strong">R'<sub>w</sub></td>
    </tr>
<?php
    $i = 1;
    foreach ($prostor->fasade as $fasada) {
?>
    <tr>
        <td>Fasada <?= $i ?>.</td>
        <td class="center"><?= $this->numFormat($fasada->povrsina) ?></td>
        <td class="center"><?= $this->numFormat($fasada->povrsina / $prostor->Sf, 2) ?> 
        <td class="center"><?= $this->numFormat($fasada->Rw, 1) ?> dB</td>
    </tr>
<?php
    }
?>
    <tr>
        <td class="right strong" colspan="2">Skupaj:</td>
        <td class="right strong">R'<sub>s,w</sub> = </td>
        <td class="center strong"><?= $this->numFormat($prostor->Rw, 1) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong" colspan="2">Min. zahteva:</td>
        <td class="right strong">R'<sub>min,w</sub> = </td>
        <td class="center strong"><?= $this->numFormat($prostor->minRw, 1) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong" colspan="3">USTREZNOST:</td>
        <td class="center strong <?= $prostor->Rw > $prostor->minRw ? 'green' : 'red' ?>">
            <?= $prostor->Rw > $prostor->minRw ? 'DA' : 'NE' ?>
        </td>
    </tr>
</table>