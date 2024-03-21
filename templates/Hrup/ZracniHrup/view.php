<?php
    use App\Calc\Hrup\ZracniHrup\Izbire\VrstaSpoja;
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
    <a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>
<h1>Hrup v zraku</h1>
<table border="1" width="100%">
    <tr>
        <td colspan="2" class="w-45">Št.:</td>
        <td colspan="2" class="left strong"><?= h($locilnaKonstrukcija->id) ?></td>
    </tr>
    <tr>
        <td colspan="2" class="w-45">Naziv ločilne konstrukcije:</td>
        <td colspan="2" class="left big strong"><?= h($locilnaKonstrukcija->naziv) ?></td>
    </tr>
    <tr>
        <th colspan="4" class="big strong">Ločilni element</th>
    </tr>
    <tr>
        <td colspan="2" class="w-45">Kontrukcija:</td>
        <td colspan="2" class="left strong">
            <?= h($locilnaKonstrukcija->locilniElement->konstrukcija->id) ?> -
            <?= h($locilnaKonstrukcija->locilniElement->konstrukcija->naziv) ?>
        </td>
    </tr>
    <tr>
        <td class="w-30">Površina:</td>
        <td class="w-15 right">S<sub>s</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($locilnaKonstrukcija->locilniElement->povrsina, 1) ?> m²</td>
    </tr>
    <tr>
        <td class="w-30">Površinska masa:</td>
        <td class="w-15 right">m'=</td>
        <td class="left strong"><?= $this->numFormat($locilnaKonstrukcija->locilniElement->povrsinskaMasa, 1) ?> kg/m²</td>
    </tr>
    <tr>
        <td class="w-30">Izolativnost:</td>
        <td class="w-15 right">R'<sub>w</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($locilnaKonstrukcija->locilniElement->konstrukcija->Rw, 0) ?> dB</td>
    </tr>
</table>

    <?php
        if (isset($locilnaKonstrukcija->stranskiElementi)) {
        foreach ($locilnaKonstrukcija->stranskiElementi as $i => $stranskiElement) {
    ?>
<table border="1" width="100%">
    <tr>
        <th colspan="4" class="big strong"><?= $i+1 ?>. Stranski element</th>
    </tr>
    <tr>
        <td colspan="2" class="w-45">Izvorna in oddajna konstrukcija:</td>
        <td colspan="2" class="left strong">
            <?= h($stranskiElement->konstrukcija->id) ?> -
            <?= h($stranskiElement->konstrukcija->naziv) ?>
        </td>
    </tr>
    <tr>
        <td class="w-30">Površina:</td>
        <td class="right w-15">S<sub>i</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($stranskiElement->povrsina, 1) ?> m²</td>
    </tr>
    <tr>
        <td class="w-30">Površinska masa:</td>
        <td class="right w-15">m'=</td>
        <td class="left strong"><?= $this->numFormat($stranskiElement->konstrukcija->povrsinskaMasa, 1) ?> kg/m²</td>
    </tr>
    <?php
        if (isset($stranskiElement->idDodatnegaSloja)) {
            $dodatniSloj = array_first($stranskiElement->konstrukcija->dodatniSloji, fn($k) => $k->id == $stranskiElement->idDodatnegaSloja)
    ?>
    <tr>
        <td class="w-30">Dodatni sloj :: <?= h($dodatniSloj->naziv) ?></td>
        <td class="right w-15">&Delta;R=</td>
        <td class="left strong">
            <?= $this->numFormat($dodatniSloj->dR, 0) ?> dB 
            (
                m'=<?= $this->numFormat($dodatniSloj->povrsinskaMasa, 1) ?> kg/m²
                <?php if ($dodatniSloj->vrsta == 'elasticen') echo ', s<sub>D</sub>=' . $this->numFormat($dodatniSloj->dinamicnaTogost, 1) . ' MN/m³'; ?>
                <?php if ($dodatniSloj->vrsta == 'nepritrjen') echo ', d=' . $this->numFormat($dodatniSloj->sirinaMedprostora, 1) . ' m'; ?>
            )
        </td>
    </tr>
    <?php
        }
    ?>
    <tr>
        <?php
            $Rw = round($stranskiElement->konstrukcija->Rw, 0);
            if (!empty($dodatniSloj)) {
                $Rw += $dodatniSloj->dR;
            }
        ?>
        <td class="w-30">Izolativnost:</td>
        <td class="right w-15">R'<sub>w</sub>=</td>
        <td colspan="2" class="left strong">
            <?= $this->numFormat($Rw, 0) ?> 
            dB
        </td>
    </tr>


    <tr>
        <td class="w-30">Spoj:</td>
        <td class="center w-15"><img src="<?= App::url('/img/zracni-hrup/spoj_' . VrstaSpoja::from($stranskiElement->vrstaSpoja)->getOrdinal() . '.png') ?>" /></td>
        <td colspan="2" class="left strong">
            <div><?= h(VrstaSpoja::from($stranskiElement->vrstaSpoja)->naziv()) ?></div>
            <div>R<sub>Df</sub> = R<sub><?= $stranskiElement->pozicijeElementov->locilni ?><?= $stranskiElement->pozicijeElementov->oddajni ?></sub> = 
                <?= $this->numFormat($stranskiElement->R_Df, 0) ?> dB</div>
            <div>R<sub>Ff</sub> = R<sub><?= $stranskiElement->pozicijeElementov->izvorni ?><?= $stranskiElement->pozicijeElementov->oddajni ?></sub> = 
                <?= $this->numFormat($stranskiElement->R_Ff, 0) ?> dB</div>
            <div>R<sub>Fd</sub> = R<sub><?= $stranskiElement->pozicijeElementov->izvorni ?><?= $stranskiElement->pozicijeElementov->locilni ?></sub> = 
                <?= $this->numFormat($stranskiElement->R_Fd, 0) ?> dB</div>
            <?php
                foreach ($stranskiElement->pozicijeElementov as $pozicija) {

                }
            ?>
        </td>
    </tr>
</table>
    <?php
            }
        }
    ?>

<table border="1" width="100%">
    <tr>
        <th colspan="4" class="big strong">REZULTAT</th>
    </tr>
    <tr>
        <td class="right strong w-30" colspan="1">Skupaj:</td>
        <td class="right strong w-15">R'<sub>w</sub> = </td>
        <td colspan="2" class="left strong">
            <?= $this->numFormat($locilnaKonstrukcija->Rw, 0) ?> dB
        </td>
    </tr>
    <tr>
        <td class="right strong w-30" colspan="1">Min. zahteva:</td>
        <td class="right strong w-15">R'<sub>min,w</sub> = </td>
        <td class="left strong"><?= $this->numFormat($locilnaKonstrukcija->minRw, 0) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong w-45" colspan="2">USTREZNOST:</td>
        <td class="left strong <?= $locilnaKonstrukcija->Rw >= $locilnaKonstrukcija->minRw ? 'green' : 'red' ?>">
            <?= $locilnaKonstrukcija->Rw >= $locilnaKonstrukcija->minRw ? 'DA' : 'NE' ?>
        </td>
    </tr>
</table>