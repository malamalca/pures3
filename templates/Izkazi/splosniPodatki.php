<?php
    use App\Core\App;
?>
<h1>Izkaz o energijskih lastnostih stavbe </h1>

<h3>Splošni podatki o stavbi: </h3>
<table border="1" cellpadding="3" width="100%">
    <tr>
        <td class="w-40">investitor:</td>
        <td class="w-60">
            <?= h(implode(PHP_EOL, array_map(fn($investitor) => implode(', ', array_filter([$investitor->naziv, $investitor->naslov])), $splosniPodatki->investitorji))) ?>
        </td>
    </tr>
    <tr>
        <td class="w-40">stavba:</td>
        <td class="w-60"> <?= h($splosniPodatki->stavba->naziv) ?></td>
    </tr>
    <tr>
        <td class="w-40">lokacija stavbe:</td>
        <td class="w-60"> <?= h($splosniPodatki->stavba->lokacija) ?></td>
    </tr>
    <tr>
        <td class="w-40">katastrska občina:</td>
        <td class="w-60"> <?= h($splosniPodatki->stavba->KO) ?></td>
    </tr>
    <tr>
        <td class="w-40">parcelna številka:</td>
        <td class="w-60"> <?= h(implode(', ', $splosniPodatki->stavba->parcele)) ?></td>
    </tr>
    <tr>
        <td class="w-40">koordinate lokacije stavbe (Y, X):</td>
        <td class="w-60"> <?= h(implode(', ', [$splosniPodatki->stavba->koordinate->Y, $splosniPodatki->stavba->koordinate->X])) ?></td>
    </tr>
    <tr>
        <td class="w-40">klasifikacija stavbe (CC-SI):</td>
        <td class="w-60"> <?= h($splosniPodatki->stavba->klasifikacija) ?></td>
    </tr>
    <tr>
        <td class="w-40">kondicionirana površina stavbe A<sub>use</sub>: </td>
        <td class="w-60"> <?= $this->numFormat($stavba->ogrevanaPovrsina, 1) ?> m²</td>
    </tr>
</table>

<h3>Vrsta stavbe: </h3>
<table cellpadding="3" width="100%">
    <tr>
        <td class="w-5 checkbox"><?= $splosniPodatki->stavba->vrsta == 'nezahtevna' ? '&#x2611;' : '&#x2610;' ?></td>
        <td>energetsko nezahtevna stavba</td>
    </tr>
    <tr>
        <td class="w-5 checkbox"><?= $splosniPodatki->stavba->vrsta == 'manjzahtevna' ? '&#x2611;' : '&#x2610;' ?></td>
        <td>energetsko manj zahtevna stavba</td>
    </tr>
    <tr>
        <td class="w-5 checkbox"><?= $splosniPodatki->stavba->vrsta == 'zahtevna' ? '&#x2611;' : '&#x2610;' ?></td>
        <td>energetsko zahtevna stavba</td>
    </tr>
</table>

<?php
    $sistemi = [
        'ogrevanje' => 'ogrevanje',
        'hlajenje' => 'hlajenje',
        'prezracevanje' => 'prezračevanje',
        'tsv' => 'priprava TSV',
        'klimatizacija' => 'klimatizacija',
        'razsvetljava' => 'razsvetljava',
        'avtomatizacija' => 'avtomatizacija in nadzor',
        'emobilnost' => 'e-mobilnost',
        'fotovoltaika' => 'proizvodnja toplote in električne energije',
        'transport' => 'transportni sistemi v stavbi',
    ];
?>
<h3>Vgrajeni TSS: </h3>
<table cellpadding="3" border="0" width="100%">
    <tr>
        <td class="w-5"></td>
        <td class="w-35"></td>
        <td class="w-30 strong center" style="border: 1px solid black;">energent(-i): </td>
        <td class="w-30 strong center" style="border: 1px solid black;">OVE:</td>
    </tr>
    <?php
        foreach ($sistemi as $slug => $naziv) {
    ?>
    <tr>
        <td class="w-5 checkbox"><?= in_array($slug, $vgrajeniSistemi) ? '&#x2611;' : '&#x2610;' ?></td>
        <td class="w-35"><?= h($naziv) ?></td>
        <td class="w-30" style="border: 1px solid black; border-top: 0px solid black;"></td>
        <td class="w-30" style="border: 1px solid black; border-top: 0px solid black;"></td>
    </tr>
    <?php
        }
    ?>
</table>


<h3>&nbsp;</h3>
<table border="1" cellpadding="3" width="100%">
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