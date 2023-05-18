<?php
    use \App\Lib\Calc;
?>
<h1></h1>

<table>
    <tr>
        <td>Naziv:</td>
        <td><?= h($kons->naziv) ?></td>
        <td colspan="4"></td>
    </tr>
    <tr>
        <td>Tip:</td>
        <td colspan="4"><?= h($kons->TSG->naziv) ?></td>
    </tr>
    <tr>
        <td>U=</td>
        <td><?= number_format($kons->U, 3) ?> W/m2K</td>
        <td>U<sub>max</sub>=</td>
        <td><?= number_format($kons->TSG->Umax, 3) ?> W/m2K</td>
        <td><?= $kons->TSG->Umax > $kons->U ? 'Ustreza' : 'Ne ustreza' ?></td>
    </tr>
    
    <tr>
        <td>f<sub>Rsi</sub>=</td>
        <td><?= number_format($kons->fRsi[0], 3) ?></td>
        <td>f<sub>Rsi,min</sub>=</td>
        <td><?= number_format(max($okolje->minfRsi), 3) ?></td>
        <td><?= max($okolje->minfRsi) < $kons->fRsi[0] ? 'Ustreza' : 'Ne ustreza' ?></td>
    </tr>
</table>
<br /><br />
<table border="1">
    <thead>
        <tr>
            <th></th>
            <th class="right">d<br />[m]</th>
            <th class="right w-10">&lambda;<br />[W/mK]</th>
            <th class="right w-10">&rho;<br />[kg/m<sup>3</sup>]</th>
            <th class="right w-10">c<sub>p</sub><br />[J/kg K]</th>
            <th class="right w-10">&mu;<br />[-]</th>
            <th class="right w-10">R<br />[m<sup>2</sup>K/W]</th>
            <th class="right w-10">s<sub>d</sub><br />[m]</th>
        </tr>
    </thead>
<?php
    foreach ($kons->materiali as $material) {
?>
    <tr>
        <td class="center"><?= h($material->opis) ?></td>
        <td class="center"><?= number_format($material->debelina, 3, ',', '') ?></td>
        <td class="center"><?= number_format($material->lambda, 3, ',', '') ?></td>
        <td class="center"><?= number_format($material->gostota, 0, ',', '') ?></td>
        <td class="center"><?= number_format($material->specificnaToplota, 0, ',', '') ?></td>
        <td class="center"><?= number_format($material->difuzijskaUpornost, 1, ',', '') ?></td>
        <td class="center"><?= number_format($material->R, 3, ',', '') ?></td>
        <td class="center"><?= number_format($material->Sd, 3, ',', '') ?></td>
    </tr>
<?php
    }
?>
</table>