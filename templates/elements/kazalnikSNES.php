<?php
/**
 * Grafični prikaz kazalnikov energijske učinkovitosti sNES (TSG-1-004: 2022, str. 103-106).
 *
 * Geometrija in barve so povzete po sliki v smernici. Postavitev je opisana v
 * tipografskih točkah smernice, ob izrisu pa preračunana v slikovne pike tako,
 * da prikaz ostane znotraj širine strani (print.css omeji telo na 18 cm).
 * Prikaz je sestavljen iz navadnih html elementov, ker wkhtmltopdf ne zna
 * izrisati SVG grafike.
 *
 * Oznaka dovoljene oziroma referenčne vrednosti je vedno na istem mestu (62,6 % traku),
 * puščica obravnavane stavbe pa je postavljena sorazmerno glede na to vrednost.
 *
 * @var \App\Core\View $this
 * @var \stdClass $kazalnik Podatki za prikaz
 */

// Slikovnih pik na točko postavitve iz smernice
$f = 1.84;
$px = fn($tocke) => (int)round($tocke * $f);

$sirina = $px(326);
$panelSirina = $px(317.8);
$trakOdmik = $px(5.1);
$trakSirina = $panelSirina - 2 * $trakOdmik;
$puscicaSirina = $px(8);
$konica = (int)round($puscicaSirina / 2);
$rep = (int)round($puscicaSirina / 2);

// Delež traku, kjer je označena dovoljena oziroma referenčna vrednost
$mejaDelez = 0.626;

$korigirano = abs($kazalnik->korigirana - $kazalnik->specificna) > 0.05;
// Puščica kaže korigirano vrednost, kadar je bil uporabljen kompenzacijski faktor
$vrednost = $korigirano ? $kazalnik->korigirana : $kazalnik->specificna;

$mejaX = $trakOdmik + $mejaDelez * $trakSirina;
$vrednostX = $mejaX;
if ($kazalnik->meja > 0) {
    $vrednostX = $trakOdmik + $mejaDelez * $trakSirina * ($vrednost / $kazalnik->meja);
}
// Puščica mora ostati znotraj traku
$vrednostX = (int)round(max(
    $trakOdmik + $puscicaSirina / 2,
    min($trakOdmik + $trakSirina - $puscicaSirina / 2, $vrednostX)
));
$mejaX = (int)round($mejaX);

// Puščica je zelena le, kadar sta pod mejo tako izračunana kot korigirana vrednost
$vrednostBarva = max($kazalnik->specificna, $kazalnik->korigirana) <= $kazalnik->meja ? '#AED361' : '#ED1C24';

$vrsticaVisina = $px(11.3);
$panelVrh = $px(27);
$panelVisina = $px(35);
$trakVrh = $panelVrh + $px(8.5);
$trakVisina = $px(17.5);
$stevilkaVrh = $panelVrh + $panelVisina + $px(1);
$oznakaVrh = $stevilkaVrh + $px(10);
$crtaVrh = ($kazalnik->mejaOznaka ? $oznakaVrh : $stevilkaVrh) + $px(11);
$visina = $crtaVrh + $px(3);

// Zeleno polje označuje izpolnjen kriterij, prazno polje neizpolnjenega oziroma informativno vrednost
$boxFill = fn($zeleno) => $zeleno ? '#AED361' : '#FFFFFF';
// Neizpolnjena zahteva ROVE je po smernici označena z obarvanim poljem
$roveFill = $kazalnik->roveUstreza ? '#AED361' : '#FFCB03';

// Decimalka se izpiše le, kadar vrednost ni zaokrožena na celo število
$stevilka = fn($v) => $this->numFormat($v, abs($v - round($v)) < 0.05 ? 0 : 1);

$oznaka = fn($znak, $indeks, $velikost = 9) => sprintf(
    '<span style="font-size:%1$dpx">%2$s</span>' .
    '<span style="font-size:%3$dpx;line-height:%3$dpx">%4$s</span>',
    $px($velikost),
    $znak,
    $px($velikost * 0.68),
    $indeks
);

$polje = fn($levo, $sirinaPolja, $vrh, $barva, $vsebina) => sprintf(
    '<span style="position:absolute;left:%1$dpx;top:%2$dpx;width:%3$dpx;height:%4$dpx;' .
    'border:1px solid #232022;background-color:%5$s;font-size:%6$dpx;line-height:%4$dpx;' .
    'text-align:center;overflow:hidden">%7$s</span>',
    $px($levo),
    $vrh,
    $px($sirinaPolja),
    $vrsticaVisina,
    $barva,
    $px(9),
    $vsebina
);

$besedilo = fn($levo, $vrh, $vsebina, $velikost = 7) => sprintf(
    '<span style="position:absolute;left:%1$dpx;top:%2$dpx;font-size:%3$dpx;' .
    'line-height:%4$dpx;white-space:nowrap">%5$s</span>',
    $px($levo),
    $vrh,
    $px($velikost),
    $vrsticaVisina,
    $vsebina
);

/**
 * Puščica navzdol nad trakom - iz pravokotnega repa in trikotne konice.
 */
$puscicaDol = fn($x, $barva) => sprintf(
    '<span style="position:absolute;left:%1$dpx;top:%2$dpx;width:%3$dpx;height:%4$dpx;' .
    'background-color:%5$s"></span>' .
    '<span style="position:absolute;left:%6$dpx;top:%7$dpx;width:0;height:0;' .
    'border-left:%8$dpx solid transparent;border-right:%8$dpx solid transparent;' .
    'border-top:%9$dpx solid %5$s"></span>',
    $x - (int)round($rep / 2),
    $panelVrh - 1,
    $rep,
    $trakVrh - $panelVrh - $konica + 2,
    $barva,
    $x - $konica,
    $trakVrh - $konica,
    $konica,
    $konica
);

/**
 * Puščica navzgor pod trakom.
 */
$puscicaGor = fn($x, $barva) => sprintf(
    '<span style="position:absolute;left:%1$dpx;top:%2$dpx;width:0;height:0;' .
    'border-left:%3$dpx solid transparent;border-right:%3$dpx solid transparent;' .
    'border-bottom:%4$dpx solid %5$s"></span>' .
    '<span style="position:absolute;left:%6$dpx;top:%7$dpx;width:%8$dpx;height:%9$dpx;' .
    'background-color:%5$s"></span>',
    $x - $konica,
    $trakVrh + $trakVisina,
    $konica,
    $konica,
    $barva,
    $x - (int)round($rep / 2),
    $trakVrh + $trakVisina + $konica,
    $rep,
    $panelVrh + $panelVisina - $trakVrh - $trakVisina - $konica
);
?>
<div class="kazalnikSNES" style="position:relative;width:<?= $sirina ?>px;height:<?= $visina ?>px">

    <?php // Izračunana specifična celotna potrebna primarna energija ?>
    <?= $besedilo(0, 1, $oznaka('E&rsquo;', 'Ptot,an')) ?>
    <?= $polje(29, 23, 1, $boxFill($kazalnik->energijaUstreza), $this->numFormat($kazalnik->specificna, 0)) ?>
    <?= $besedilo(55, 1, 'kWh/(m<sup>2</sup> an)') ?>

    <?php // Razmernik obnovljivih virov energije ?>
    <?= $besedilo(96, 1, 'ROVE', 9) ?>
    <?= $polje(121, 23, 1, $roveFill, $this->numFormat($kazalnik->rove, 0)) ?>
    <?= $besedilo(147, 1, '%') ?>

    <?php // Izpusti CO2 ?>
    <?= $besedilo(243, 1, $oznaka('M', 'CO2')) ?>
    <?= $polje(266, 33, 1, $boxFill($kazalnik->co2Ustreza), $this->numFormat($kazalnik->co2, 0)) ?>
    <?= $besedilo(302, 1, 'kg/an') ?>

    <?php
    // Vrstica z vrednostjo, ki jo kaže puščica
    $vrsticaVrh = $vrsticaVisina + $px(2);
    if ($korigirano) {
        // Polje ostane med oznako na levi in enoto na desni
        $poljeSirina = 26;
        $poljeX = (int)round(max($px(60), min($px(210), $vrednostX - $px($poljeSirina) / 2)));
    ?>
    <span style="position:absolute;left:0;top:<?= $vrsticaVrh ?>px;width:<?= $poljeX - $px(2) ?>px;
                 font-size:<?= $px(8) ?>px;line-height:<?= $vrsticaVisina ?>px;text-align:right;
                 white-space:nowrap"><?= $oznaka('E&rsquo;', 'Ptot,kor,an', 8) ?></span>
    <span style="position:absolute;left:<?= $poljeX ?>px;top:<?= $vrsticaVrh ?>px;width:<?= $px($poljeSirina) ?>px;
                 height:<?= $vrsticaVisina ?>px;border:1px solid #232022;background-color:#FFFFFF;
                 font-size:<?= $px(9) ?>px;line-height:<?= $vrsticaVisina ?>px;text-align:center;
                 overflow:hidden"><?= $stevilka($kazalnik->korigirana) ?></span>
    <span style="position:absolute;left:<?= $poljeX + $px($poljeSirina + 4) ?>px;top:<?= $vrsticaVrh ?>px;
                 font-size:<?= $px(7) ?>px;line-height:<?= $vrsticaVisina ?>px;
                 white-space:nowrap">kWh/(m<sup>2</sup> an)</span>
    <?php } else { ?>
    <span style="position:absolute;left:0;top:<?= $vrsticaVrh ?>px;width:<?= $vrednostX + $px(30) ?>px;
                 font-size:<?= $px(8) ?>px;line-height:<?= $vrsticaVisina ?>px;text-align:right;
                 white-space:nowrap"><?= $oznaka('E&rsquo;', 'Ptot,an', 8) ?></span>
    <?php } ?>

    <?php // Trak ?>
    <span style="position:absolute;left:0;top:<?= $panelVrh ?>px;width:<?= $panelSirina ?>px;
                 height:<?= $panelVisina ?>px;background-color:#F4F6F7;border-radius:<?= $px(4) ?>px"></span>
    <span style="position:absolute;left:<?= $trakOdmik ?>px;top:<?= $trakVrh ?>px;width:<?= $trakSirina ?>px;
                 height:<?= $trakVisina ?>px;background-color:#C0BEBE"></span>

    <?= $puscicaDol($vrednostX, $vrednostBarva) ?>
    <?= $puscicaGor($mejaX, '#ED1C24') ?>

    <?php // Dovoljena oziroma referenčna vrednost ?>
    <span style="position:absolute;left:0;top:<?= $stevilkaVrh ?>px;width:<?= $mejaX * 2 ?>px;
                 font-size:<?= $px(9) ?>px;line-height:<?= $px(10) ?>px;text-align:center"><?= $stevilka($kazalnik->meja) ?></span>
<?php if ($kazalnik->mejaOznaka) { ?>
    <span style="position:absolute;left:0;top:<?= $oznakaVrh ?>px;width:<?= $mejaX * 2 ?>px;
                 font-size:<?= $px(8) ?>px;line-height:<?= $px(10) ?>px;
                 text-align:center"><?= $oznaka('E&rsquo;', $kazalnik->mejaOznaka, 8) ?></span>
<?php } ?>
    <span style="position:absolute;left:0;top:<?= $stevilkaVrh ?>px;width:<?= $sirina ?>px;
                 font-size:<?= $px(7) ?>px;line-height:<?= $px(10) ?>px;
                 text-align:right">kWh/(m<sup>2</sup> an)</span>

    <span style="position:absolute;left:0;top:<?= $crtaVrh ?>px;width:<?= $sirina ?>px;
                 height:2px;background-color:#F6881E"></span>
</div>
