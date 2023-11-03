<?php
    use App\Core\App;
    use App\Lib\Calc;

    use App\Calc\Hrup\ZunanjiHrup\Izbire\ObmocjeZascitePredHrupom;
    use App\Calc\Hrup\ZunanjiHrup\Izbire\VrstaKazalcevHrupa;
?>
<h2>Izjava izdelovalca elaborata zaščite pred hrupom</h2>
<p class="actions">
<a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>
<p>Po Pravilniku o zaščiti pred hrupom v stavbah (Uradni list RS, št. 10/12) </p>
<table width="100%">
    <tr>
        <td class="w-30">Objekt:</td>
        <td class="w-70"><?= h($splosniPodatki->stavba->naziv) ?></td>
    </tr>
    <tr>
        <td class="w-30">Lokacija:</td>
        <td class="w-70"><?= h(implode(', ', $splosniPodatki->stavba->parcele)) ?>
            k.o. <?= h($splosniPodatki->stavba->KO) ?></td>
    </tr>
    <tr>
        <td class="w-30">CC-Si Klasifikacija:</td>
        <td class="w-70"><?= h($splosniPodatki->stavba->klasifikacija) ?></td>
    </tr>
    <tr>
        <td class="w-30">Ravni hrupa:</td>
        <td class="w-70"><?= VrstaKazalcevHrupa::from($splosniPodatki->zunanjiHrup->kazalciHrupa) == VrstaKazalcevHrupa::GledeNaObmocje ? 'glede na območje' : 'izmerjene ali izračunane' ?></td>
    </tr>
    <tr>
        <td class="w-30">Območje varstva pred hrupom:</td>
        <td class="w-70"><?= h($splosniPodatki->zunanjiHrup->obmocje) ?>. območje</td>
    </tr>
    <tr>
        <td class="w-30">Okoljska meja zunanjega hrupa:</td>
        <td class="w-70"><?= ObmocjeZascitePredHrupom::from($splosniPodatki->zunanjiHrup->obmocje)->kazalci('Ldvn') ?> dBA</td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">Spodaj podpisani izdelovalec elaborata Zaščite pred hrupom izjavljam, da je elaborat skladen z veljavnim  pravilnikom in tehnično smernico.</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr><td colspan="2">&nbsp;</td></tr>
    <tr>
        <td class="w-100" colspan="2">
            <?= h($splosniPodatki->datum) ?>
            <div style="float:right;"><?= h($splosniPodatki->izdelovalec) ?></div>
        </td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
</table>