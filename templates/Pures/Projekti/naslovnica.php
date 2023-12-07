<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>
<h1>Energijska učinkovitost stavb</h1>
<p>Po Pravilniku o učinkoviti rabi energije v stavbah (Uradni list RS, št. 70/22 z dne 20. 5. 2022) in 
Pravilniku o spremembah Pravilnika o učinkoviti rabi energije v stavbah (Uradni list RS, št. 161/22 z dne 23. 12. 2022).
</p>
<table width="100%">
    <tr><td colspan="2">&nbsp;</td></tr>
    <?php
        foreach ($splosniPodatki->investitorji as $investitor) {
    ?>
    <tr>
        <td class="w-30">Investitor:</td>
        <td class="w-70"><?= h($investitor->naziv) ?></td>
    </tr>
    <tr>
        <td class="w-30"></td>
        <td class="w-70"><?= h($investitor->naslov) ?></td>
    </tr>
    <?php
        }
    ?>
    <tr><td colspan="2">&nbsp;</td></tr>

    <tr>
        <td class="w-30">Naziv projekta:</td>
        <td class="w-70"><h3><?= h($splosniPodatki->stavba->naziv) ?></h3></td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>

    <tr>
        <td class="w-30">Izdelovalec elaborata:</td>
        <td class="w-70"><?= h($splosniPodatki->izdelovalec) ?></td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr>
        <td class="w-30">Vodja projektiranja:</td>
        <td class="w-70"><?= h($splosniPodatki->vodjaProjektiranja) ?></td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr>
        <td class="w-30">Številka elaborata:</td>
        <td class="w-70"><?= h($splosniPodatki->stevilkaElaborata) ?></td>
    </tr>
    <tr>
        <td class="w-30">Datum elaborata:</td>
        <td class="w-70"><?= h($splosniPodatki->datum) ?></td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
</table>