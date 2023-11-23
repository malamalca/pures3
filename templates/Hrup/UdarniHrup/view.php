<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Udarni hrup</h1>

<p class="actions">
    <a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<table border="1">
    <tr>
        <td colspan="2" class="w-30">Št.:</td>
        <td colspan="2" class="left strong"><?= h($locilnaKonstrukcija->id) ?></td>
    </tr>
    <tr>
        <td colspan="2" class="w-30">Naziv ločilne konstrukcije:</td>
        <td colspan="2" class="left strong"><?= h($locilnaKonstrukcija->naziv) ?></td>
    </tr>
    <!--<tr>
        <td class="w-20">Prostornina sprejemnega prostora:</td>
        <td class="w-10 right strong">V=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($locilnaKonstrukcija->prostorninaSprejemnegaProstora, 1) ?> m3</td>
    </tr>-->
    <tr>
        <td class="w-20">Ekvivalentna ovrednotena normirana raven zvočnega tlaka:</td>
        <td class="w-10 right strong">L<sub>n,w,eq</sub>=</td>
        <td colspan="2" class="left strong">
            `=164 − 35 log((m')/(m'_0))` pri `m'_0 = 1 (kg)/(m^2)`<br />
            `=164 − 35 log(<?= $this->numFormat($locilnaKonstrukcija->konstrukcija->povrsinskaMasa, 0, '.') ?>/1)=<?= $this->numFormat($locilnaKonstrukcija->Lnweq, 1) ?>~~`
            <?= $this->numFormat($locilnaKonstrukcija->Lnweq, 0) ?> dB
        </td>
    </tr>
    <tr>
        <td class="w-20">Korekcija za stranski prenos (po tabeli):</td>
        <td class="w-10 right strong">K=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($locilnaKonstrukcija->K, 0) ?> dB</td>
    </tr>
    <tr>
        <td class="w-20">Ovrednoteno izboljšanje izolacije zaradi dodatnih slojev:</td>
        <td class="w-10 right strong">&Delta;L<sub>w</sub>=</td>
        <td colspan="2" class="left strong"><?= $this->numFormat($locilnaKonstrukcija->deltaL, 0) ?> dB</td>
    </tr>

    
    <tr>
        <td class="right strong" colspan="1">Skupaj:</td>
        <td class="right strong">L'<sub>n,w</sub> = </td>
        <td colspan="2" class="left strong">
            `L_(n,w,eq) − ΔL_w + K = (<?= $this->numFormat($locilnaKonstrukcija->Lnweq, 0, '.') ?> − <?= $this->numFormat($locilnaKonstrukcija->deltaL, 0, '.') ?> + <?= $this->numFormat($locilnaKonstrukcija->K, 0, '.') ?>) =`
            <?= $this->numFormat($locilnaKonstrukcija->Lnw, 0) ?> dB
        </td>
    </tr>
    <tr>
        <td class="right strong" colspan="1">Min. zahteva:</td>
        <td class="right strong nowrap">L'<sub>n,w,min</sub> = </td>
        <td class="left strong"><?= $this->numFormat($locilnaKonstrukcija->minLnw, 0) ?> dB</td>
    </tr>
    <tr>
        <td class="right strong" colspan="2">USTREZNOST:</td>
        <td class="left strong <?= $locilnaKonstrukcija->Lnw <= $locilnaKonstrukcija->minLnw ? 'green' : 'red' ?>">
            <?= $locilnaKonstrukcija->Lnw <= $locilnaKonstrukcija->minLnw ? 'DA' : 'NE' ?>
        </td>
    </tr>
</table>