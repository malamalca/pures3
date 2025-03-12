<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
<a class="button active" href="<?= App::url('/pures/cone/analiza/' . $projectId . '/' . $cona->id) ?>">Analiza cone "<?= $cona->id ?>"</a>
<a class="button" href="<?= App::url('/pures/cone/ovoj/' . $projectId . '/' . $cona->id) ?>">Analiza ovoja cone "<?= $cona->id ?>"</a>
</p>

<h1>Splošni podatki cone "<?= h($cona->naziv) ?>"</h1>
<table border="1">
    <tr>
        <td colspan="2">Klasifikacija</td>
        <td class="left"><?= h($cona->klasifikacija) ?></td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>Bruto ogrevana prostornina cone</td>
        <td>V<sub>e</sub></td>
        <td class="center"><?= $this->numFormat($cona->brutoProstornina, 1) ?></td>
        <td>m³</td>
    </tr>
    <tr>
        <td>Neto prostornina cone</td>
        <td>V<sub>neto</sub></td>
        <td class="center"><?= $this->numFormat($cona->netoProstornina, 1) ?></td>
        <td>m³</td>
    </tr>
    <tr>
        <td>Kondicionirana površina cone</td>
        <td>A<sub>use</sub></td>
        <td class="center"><?= $this->numFormat($cona->ogrevanaPovrsina, 1) ?></td>
        <td>m²</td>
    </tr>
    <tr>
        <td>Površina toplotnega ovoja cone</td>
        <td>A<sub>ovoj</sub></td>
        <td class="center"><?= $this->numFormat($cona->povrsinaOvoja, 1) ?></td>
        <td>m²</td>
    </tr>
    <tr>
        <td>Transp. površina v toplotnem ovoju stavbe</td>
        <td>A<sub>trans</sub></td>
        <td class="center"><?= $this->numFormat($cona->transparentnaPovrsina, 2) ?></td>
        <td>m²</td>
    </tr>
    <tr>
        <td>Dolžina cone</td>
        <td>D</td>
        <td class="center"><?= $this->numFormat($cona->dolzina, 1) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td>Širina cone</td>
        <td>Š</td>
        <td class="center"><?= $this->numFormat($cona->sirina, 1) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td>Etažna višina</td>
        <td>H<sub>floor</sub></td>
        <td class="center"><?= $this->numFormat($cona->etaznaVisina, 1) ?></td>
        <td>m</td>
    </tr>
    <tr>
        <td>Število etaž</td>
        <td>n<sub>floor</sub></td>
        <td class="center"><?= $this->numFormat($cona->steviloEtaz, 1) ?></td>
        <td>-</td>
    </tr>
</table>
<h1>Drugi podatki cone "<?= h($cona->naziv) ?>"</h1>
<table border="1">
    <tr>
        <td colspan="2">Sistem prezračevanja:</td>
        <td class="center"><?= h($cona->prezracevanje->vrsta ?? 'nedefinirano') ?></td>
        <td>-</td>
    </tr>
    <tr>
        <td colspan="4">Dotok svežega zraka: </td>
    </tr>
    <tr>
        <td>- v času ogrevanja: </td>
        <td>V<sub>air,h</sub></td>
        <td class="center"><?= $this->numFormat($cona->volumenZrakaOgrevanje, 1) ?></td>
        <td>m³/h</td>
    </tr>
    <tr>
        <td>- v času hlajenja: </td>
        <td>V<sub>air,c</sub></td>
        <td class="center"><?= $this->numFormat($cona->volumenZrakaHlajenje, 1) ?></td>
        <td>m³/h</td>
    </tr>
</table>
<h1>Analiza cone "<?= h($cona->naziv) ?>"</h1>
<table border="1">
<thead>
        <tr>
            <td>Ogrevanje</td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
</thead>
        <tr>
            <td>Transmisijske izgube</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $cona->transIzgubeOgrevanje)) ?>
        </tr>

        <tr>
            <td>Prezračevalne izgube</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $cona->prezracevalneIzgubeOgrevanje)) ?>
        </tr>

        <tr>
            <td>Dobitki notranjih bremen</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $cona->notranjiViriOgrevanje)) ?>
        </tr>

        <tr>
            <td>Dobitki sončnega obsevanja</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $cona->solarniDobitkiOgrevanje)) ?>
        </tr>

        <tr>
            <td>Faktor izkoristljivosti dobitkov</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . ($mesecnaVrednost ? $this->numFormat($mesecnaVrednost, 3) : '') . '</td>', $cona->ucinekDobitkov)) ?>
        </tr>

        <tr>
            <td>Q<sub>H,nd,zn,m</sub>; Q<sub>H,nd,zn,an</sub></td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . ($mesecnaVrednost ? $this->numFormat($mesecnaVrednost, 0) : '') . '</td>', $cona->energijaOgrevanje)) ?>
            <td class="center"><?= $this->numFormat($cona->skupnaEnergijaOgrevanje, 0) ?></td>
        </tr>


    <tr><td colspan="14">&nbsp;</td></tr>
    <thead>
        <tr>
            <td>Hlajenje</td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>

        <tr>
            <td>Transmisijske izgube</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $cona->transIzgubeHlajenje)) ?>
        </tr>

        <tr>
            <td>Prezračevalne izgube</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $cona->prezracevalneIzgubeHlajenje)) ?>
        </tr>

        <tr>
            <td>Dobitki notranjih bremen</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $cona->notranjiViriHlajenje)) ?>
        </tr>

        <tr>
            <td>Dobitki sončnega obsevanja</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . $this->numFormat($mesecnaVrednost, 0) . '</td>', $cona->solarniDobitkiHlajenje)) ?>
        </tr>

        <tr>
            <td>Faktor izkoristljivosti ponorov</td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . ($mesecnaVrednost ? $this->numFormat($mesecnaVrednost, 3) : '') . '</td>', $cona->ucinekPonorov)) ?>
        </tr>

        <tr>
            <td>Q<sub>C,nd,zn,m</sub>; Q<sub>C,nd,zn,an</sub></td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . ($mesecnaVrednost ? $this->numFormat($mesecnaVrednost, 1) : '') . '</td>', $cona->energijaHlajenje)) ?>
            <td class="center"><?= $this->numFormat($cona->skupnaEnergijaHlajenje, 0) ?></td>
        </tr>

        <tr><td colspan="14">&nbsp;</td></tr>


    <thead>
        <tr>
            <td>TSV, navlaž./razvlaž. zraka</td>
            <?= implode(PHP_EOL, array_map(fn($mes) => '<td class="center">' . $mes . '</td>', Calc::MESECI)) ?>
            <td class="center">kWh/an</td>
        </tr>
    </thead>

        <tr>
            <td>Priprava TSV - Q<sub>W,nd,zn</sub></td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . ($mesecnaVrednost ? $this->numFormat($mesecnaVrednost, 0) : '') . '</td>', $cona->energijaTSV)) ?>
            <td class="center"><?= $this->numFormat($cona->skupnaEnergijaTSV, 0) ?></td>
        </tr>

        <tr>
            <td>Navlazevanje - Q<sub>U,nd,zn</sub></td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . ($mesecnaVrednost ? $this->numFormat($mesecnaVrednost, 0) : '') . '</td>', $cona->energijaNavlazevanje)) ?>
            <td class="center"><?= $this->numFormat($cona->skupnaEnergijaNavlazevanje, 0) ?></td>
        </tr>
        <tr>
            <td>Razvlazevanje - Q<sub>DHU,nd,zn</sub></td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . ($mesecnaVrednost ? $this->numFormat($mesecnaVrednost, 0) : '') . '</td>', $cona->energijaRazvlazevanje)) ?>
            <td class="center"><?= $this->numFormat($cona->skupnaEnergijaRazvlazevanje, 0) ?></td>
        </tr>

        <tr>
            <td>Razsvetljava - E<sub>L,del,an,zn</sub></td>
            <?= implode(PHP_EOL, array_map(fn($mesecnaVrednost) => '<td class="center">' . ($mesecnaVrednost ? $this->numFormat($mesecnaVrednost, 0) : '') . '</td>', $cona->energijaRazsvetljava)) ?>
            <td class="center"><?= $this->numFormat($cona->skupnaEnergijaRazsvetljava, 0) ?></td>
        </tr>
</table>

<p>&nbsp;</p>

<table border="1">
    <tr>
        <td>Specifične transmisijske izgube</td>
        <td>H<sub>tr</sub></td>
        <td class="center"><?= $this->numFormat($cona->specTransmisijskeIzgube, 1) ?></td>
        <td>W/K</td>
    </tr>
    <tr>
        <td>Specifične ventilacijske izgube</td>
        <td>H<sub>ve</sub></td>
        <td class="center"><?= $this->numFormat($cona->specVentilacijskeIzgube, 1) ?></td>
        <td>W/K</td>
    </tr>
    <tr>
        <td>Površina celotnega ovoja</td>
        <td>A</td>
        <td class="center"><?= $this->numFormat($cona->povrsinaOvoja, 1) ?></td>
        <td>m²</td>
    </tr>
    <tr>
        <td>Površina transparentnega dela ovoja</td>
        <td>A<sub>trans</sub></td>
        <td class="center"><?= $this->numFormat($cona->transparentnaPovrsina, 1) ?></td>
        <td>m²</td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>
    <tr>
        <td>Specifični koeficient transmisijskih toplotnih izgub</td>
        <td>H'<sub>tr,zn</sub></td>
        <td class="center"><?= $this->numFormat($cona->specKoeficientTransmisijskihIzgub, 3) ?></td>
        <td>W/m²K</td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>
    <tr>
        <td>Specifična potrebna toplota za ogrevanje</td>
        <td>Q'<sub>H,nd,zn,an</sub></td>
        <td class="center"><?= $this->numFormat($cona->specLetnaToplota, 1) ?></td>
        <td>kWh/m²a</td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>
    <tr>
        <td>Specifični letni potrebni hlad</td>
        <td>Q'<sub>C,nd,zn,an</sub></td>
        <td class="center"><?= $this->numFormat($cona->specLetniHlad, 1) ?></td>
        <td>kWh/m²a</td>
    </tr>
</table
