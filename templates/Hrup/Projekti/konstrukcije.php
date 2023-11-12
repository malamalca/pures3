<?php
    use App\Calc\Hrup\Elementi\Izbire\VrstaDodatnegaSloja;
?>
<h1>Konstrukcije projekta</h1>
<?php
    foreach ($konstrukcije as $konstrukcija) {
?>
    
    <table>
    <tr>
        <td colspan="3" class="strong big" style="border-bottom: solid 1px black;"><?= h($konstrukcija->id) ?> - <?= h($konstrukcija->naziv) ?></td>
    </tr>
    <tr>
        <td>Površinska masa:</td>
        <td class="right">m'=</td>
        <td><?= $this->numFormat($konstrukcija->povrsinskaMasa, 1) ?> kg/m<sup>2</sup></td>
    </tr>
    <tr>
        <td>Izolativnost:</td>
        <td class="right">Rw (C; C<sub>tr</sub>)=</td>
        <td>
            <?= $this->numFormat($konstrukcija->Rw, 1) ?>
            (<?= $this->numFormat($konstrukcija->C, 0) ?>; <?= $this->numFormat($konstrukcija->Ctr, 0) ?>) dB
        </td>
    </tr>
    <?php
        $i = 1;
        foreach ($konstrukcija->dodatniSloji as $dodatniSloj) {
    ?>
        <tr>
            <td class="left strong" colspan="3">Dodatni sloj <?= $i ?></td>
        </tr>
        <tr>
            <td colspan="2">Vrsta:</td>
            <td class="strong"><?= h(VrstaDodatnegaSloja::from($dodatniSloj->vrsta)->naziv()) ?></td>
        </tr>
        <tr>
            <td>Površinska masa:</td>
            <td class="right">m'=</td>
            <td><?= $this->numFormat($dodatniSloj->povrsinskaMasa, 1) ?> kg/m<sup>2</sup></td>
        </tr>
        <tr>
            <td>Izolativnost:</td>
            <td class="right">&Delta;R=</td>
            <td><?= $this->numFormat($dodatniSloj->dR, 1) ?> dB</td>
        </tr>
        <?php
            if ($dodatniSloj->vrsta == 'elasticen') {
        ?>
        <tr>
            <td>Dinamična togost:</td>
            <td class="right">S<sub>D</sub>=</td>
            <td><?= $this->numFormat($dodatniSloj->dinamicnaTogost, 1) ?> NM/m<sup>3</sup></td>
        </tr>
        <?php
            }
        ?>
        <?php
            if ($dodatniSloj->vrsta == 'nepritrjen') {
        ?>
        <tr>
            <td>Širina medprostora:</td>
            <td class="right">d=</td>
            <td><?= $this->numFormat($dodatniSloj->sirinaMedprostora, 1) ?> m</td>
        </tr>
        <?php
            }
        ?>
    <?php
            $i++;
        }
    ?>
    </table>

    <br /><br />
<?php
    }
?>