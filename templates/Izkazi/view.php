<?php


?>
<h1>Izkaz o energijskih lastnostih stavbe</h1>

<h2>Splošni podatki o stavbi:</h2>
<table border="1" width="100%">
    <tr>
        <td class="w-40">investitor:</td>
        <td class="w-60">
        <?php
            foreach($splosniPodatki->investitorji as $investitor) {
                echo implode('</div><div>', array_map(fn($investitor) => h($investitor->naziv . ', ' . $investitor->naslov), $splosniPodatki->investitorji));
            }
        ?>
        </td>
    </tr>
    <tr>
        <td class="w-40">stavba:</td>
        <td class="w-60"><?= h($splosniPodatki->stavba->naziv) ?></td>
    </tr>
    <tr>
        <td class="w-40">lokacija stavbe:</td>
        <td class="w-60"><?= h($splosniPodatki->stavba->lokacija) ?></td>
    </tr>
    <tr>
        <td class="w-40">katastrska občina:</td>
        <td class="w-60"><?= h($splosniPodatki->stavba->KO) ?></td>
    </tr>
    <tr>
        <td class="w-40">parcelna številka:</td>
        <td class="w-60"><?= h(implode(', ', $splosniPodatki->stavba->parcele)) ?></td>
    </tr>
    <tr>
        <td class="w-40">koordinate lokacije stavbe (Y, X):</td>
        <td class="w-60"><?= h($splosniPodatki->stavba->koordinate->Y) ?>, <?= h($splosniPodatki->stavba->koordinate->X) ?></td>
    </tr>
    <tr>
        <td class="w-40">klasifikacija stavbe (CC-SI):</td>
        <td class="w-60"><?= h($splosniPodatki->stavba->klasifikacija) ?></td>
    </tr>
    <tr>
        <td class="w-40">kondicionirana površina stavbe A<sub>use</sub>:</td>
        <td class="w-60"></td>
    </tr>
</table>


<h2>Vrsta stavbe:</h2>
    <div><span class="checkbox"><?= ($splosniPodatki->stavba->vrsta == 'nezahtevna') ? '&check;' : '' ?></span> energetsko nezahtevna stavba</div>
    <div><span class="checkbox"><?= ($splosniPodatki->stavba->vrsta == 'manjzahtevna') ? '&check;' : '' ?></span> energetsko manj zahtevna stavba</div>
    <div><span class="checkbox"><?= ($splosniPodatki->stavba->vrsta == 'zahtevna') ? '&check;' : '' ?></span> energetsko zahtevna stavba</div>

<h2>Vgrajeni TSS:</h2>
<table width="100%">
    <tr>
        <th class="w-40">&nbsp;</th>
        <th class="w-30 border">energent(-i):</th>
        <th class="w-30 border">OVE:</th>
    </tr>
<?php
    $availableTSSs = [
        'ogrevanje' => 'ogrevanje',
        'hlajenje' => 'hlajenje',
        'prezracevanje' => 'prezračevanje',
        'tsv' => 'priprava TSV',
        'klimatizacija' => 'klimatizacija',
        'razsvetljava' => 'razsvetljava',
        'avtomatizacija' => 'avtomatizacija in nadzor',
        'emobilnost' => 'e-mobilnost',
        'spte' => 'proizvodnja toplote in električne energije',
        'transport' => 'transportni sistemi v stavbi',
    ];

    foreach ($availableTSSs as $TSSKey => $TSSName) {
?>
    <tr>
        <td class="w-40"><span class="checkbox"><?= isset($TSS[$TSSKey]) ? '&check;' : '' ?></span> <?= h($TSSName) ?></td>
        <td class="w-30 border center"><?= isset($TSS[$TSSKey]) ? h($TSS[$TSSKey]->energent) : '' ?></td>
        <td class="w-30 border center"><?= !empty($TSS[$TSSKey]->OVE) ? 'da' : '' ?></td>
    </tr>
<?php
    }
?>
</table>


<h2>&nbsp;</h2>
<table border="1" width="100%">
    <tr>
        <td class="w-40">vodja projektiranja:</td>
        <td class="w-60"><?= h($splosniPodatki->vodjaProjektiranja) ?></td>
    </tr>
    <tr>
        <td class="w-40">izdelovalec/-lci izkaza in njegov podpis:</td>
        <td class="w-60"><?= h($splosniPodatki->izdelovalec) ?></td>
    </tr>
    <tr>
        <td class="w-40">datum izdelave:</td>
        <td class="w-60"><?= h($splosniPodatki->datum) ?></td>
    </tr>
</table>