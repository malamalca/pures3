<?php
/**
 * Opozorilo ob neizpolnjenem razmerniku ROVE.
 *
 * Kompenzacijski faktor YROVE se po četrtem odstavku 14. člena pravilnika
 * (Ur. l. RS, št. 70/22) sme uporabiti le, kadar predpisanega ROVE ni mogoče
 * zagotoviti niti po drugem niti po tretjem odstavku istega člena, pri čemer
 * mora biti dosežen vsaj polovični zahtevani delež ROVEmin.
 *
 * @var \App\Core\View $this
 * @var \stdClass $stavba
 */

if (!isset($stavba->ROVE) || !isset($stavba->minROVE) || $stavba->ROVE >= $stavba->minROVE) {
    return;
}

$opozorila = [];

if (isset($stavba->roveNiMogoceZagotoviti) && !$stavba->roveNiMogoceZagotoviti) {
    $opozorila[] = 'Kompenzacijski faktor Y<sub>ROVE</sub> ni upoštevan, ker je med splošnimi podatki ' .
        'navedeno, da je predpisani ROVE mogoče zagotoviti po drugem oziroma tretjem odstavku ' .
        '14. člena pravilnika (tehnologije OVE v bližini stavbe oziroma delež oddaljenih sistemov).';
} elseif (!($stavba->roveKompenzacijaDopustna ?? true)) {
    $opozorila[] = sprintf(
        'Razmernik ROVE (%s %%) je nižji od polovice zahtevanega ROVE<sub>min</sub> (%s %%), zato ' .
        'uporaba kompenzacijskega faktorja Y<sub>ROVE</sub> po četrtem odstavku 14. člena pravilnika ' .
        'ni dopustna. Izračunana korigirana vrednost je zato zgolj informativna.',
        $this->numFormat($stavba->ROVE, 1),
        $this->numFormat($stavba->minROVE / 2, 1)
    );
}

if (empty($opozorila)) {
    return;
}
?>
<p class="opozorilo">
<?php foreach ($opozorila as $opozorilo) { ?>
    <?= $opozorilo ?>
<?php } ?>
</p>
