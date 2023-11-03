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
        <td colspan="2" class="w-40">Št.:</td>
        <td colspan="2" class="left strong"><?= h($prostor->id) ?></td>
    </tr>
    <tr>
        <td colspan="2" class="w-40">Naziv prostora:</td>
        <td colspan="2" class="left strong"><?= h($prostor->naziv) ?></td>
    </tr>
    <tr>
        <td class="w-20">Prostornina:</td>
        <td class="w-20 right strong">V=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->prostornina, 1) ?> m3</td>
    </tr>
    <tr>
        <td class="w-20">Odmevni čas:</td>
        <td class="w-20 right strong">t<sub>0</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($prostor->odmevniCas, 1) ?> s</td>
    </tr>
    <tr>
        <td class="w-20">Nivo hrupa v prostoru:</td>
        <td class="w-20 right strong">L<sub>notri</sub>=</td>
        <td colspan="2" class="left strong"><?= h($prostor->Lmax) ?> dBA</td>
    </tr>
    <tr>
        <td class="w-20">Nivo zunanjega hrupa:</td>
        <td class="w-20 right strong">L<sub>zunaj, 2m</sub>=</td>
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
        <td class="w-20 right strong">A=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($fasada->povrsina) ?> m2</td>
    </tr>
    <tr>
        <td class="w-20">Faktor oblike:</td>
        <td class="w-20 right strong">&Delta;L<sub>fs</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($fasada->dRw_fasada, 0) ?> dB</td>
    </tr>
</table>
<?php
    }
?>