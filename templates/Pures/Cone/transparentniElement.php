<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
<a class="button active" href="<?= App::url('/pures/cone/ovoj/' . $projectId . '/' . $cona->id) ?>">&larr;  Nazaj</a>
</p>

<h1>Transparentna konstrukcija "<?= h($kons->id) ?>"</h1>

<table border="1">
    <tr>
        <td>Površina elementa</td>
        <td>A</td>
        <td class="center"><?= $this->numFormat($kons->povrsina, 2) ?></td>
        <td>m²</td>
    </tr>
    <tr>
        <td>Toplotna izolativnost</td>
        <td>U</td>
        <td class="center"><?= $this->numFormat($kons->U, 2) ?></td>
        <td>W/m²K</td>
    </tr>
    <tr>
        <td colspan="2">Orientacija</td>
        <td class="center"><?= $kons->orientacija ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td colspan="2">Naklon</td>
        <td class="center"><?= $this->numFormat($kons->naklon, 0) ?></td>
        <td>°</td>
    </tr>

    <tr>
        <td>Širina stekla</td>
        <td>L<sub>g</sub></td>
        <td class="center"><?= $this->numFormat($kons->sirinaStekla, 2) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td>Višina stekla</td>
        <td>H<sub>g</sub></td>
        <td class="center"><?= $this->numFormat($kons->visinaStekla, 2) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td colspan="2">Delež okvirja</td>
        <td class="center"><?= $this->numFormat($kons->delezOkvirja, 2) ?></td>
        <td>-</td>
    </tr>

    <tr>
        <td>Dolžina okvirja (topl. mostu)</td>
        <td>L<sub>f</sub></td>
        <td class="center"><?= $this->numFormat($kons->dolzinaOkvirja, 2) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td>Linearna topl. prehodnost distančnika</td>
        <td>&psi;<sub>d</sub></td>
        <td class="center"><?= $this->numFormat($kons->konstrukcija->Psi, 2) ?></td>
        <td>W/mK</td>
    </tr>

    <tr>
        <td>Energijska prehodnost zasteklitve</td>
        <td>g</td>
        <td class="center"><?= $this->numFormat($kons->g, 2) ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td>Faktor senčil</td>
        <td>F<sub>sh</sub></td>
        <td class="center"><?= $this->numFormat($kons->faktorSencil, 2) ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td>Faktor senčenja</td>
        <td>g<sub>tot,sh</sub></td>
        <td class="center"><?= $this->numFormat($kons->g_sh, 2) ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td rowspan="6">Stransko senčenje</td>
        <td title="Dolžina zgornjega previsa">L<sub>sh,zgoraj</sub></td>
        <td class="center"><?= $this->numFormat($kons->stranskoSencenje->zgorajDolzina, 2) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td title="Višina od stekla do previsa">H<sub>sh,zgoraj</sub></td>
        <td class="center"><?= $this->numFormat($kons->stranskoSencenje->zgorajRazdalja, 2) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td title="Dolžina stranskega senčenja">L<sub>sh,left</sub></td>
        <td class="center"><?= $this->numFormat($kons->stranskoSencenje->levoDolzina, 2) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td title="Razdalja od stekla do stranskega elementa">D<sub>sh,left</sub></td>
        <td class="center"><?= $this->numFormat($kons->stranskoSencenje->levoRazdalja, 2) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td title="Dolžina stranskega senčenja">L<sub>sh,right</sub></td>
        <td class="center"><?= $this->numFormat($kons->stranskoSencenje->desnoDolzina, 2) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td title="Razdalja od stekla do stranskega elementa">D<sub>sh,right</sub></td>
        <td class="center"><?= $this->numFormat($kons->stranskoSencenje->desnoRazdalja, 2) ?></td>
        <td>m</td>
    </tr>
</table
