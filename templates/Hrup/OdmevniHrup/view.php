<?php
    use App\Calc\Hrup\OdmevniHrup\Prostor;
use App\Core\App;

    $frekvence = Prostor::FREKVENCE;
    $num = fn($v, $dec = 2) => $v === null ? '—' : $this->numFormat($v, $dec);
    $vrednost = fn($s, $f) => ((array)$s)[$f] ?? null;
    $daNe = fn(?bool $v) => $v === null ? 'Ni določljivo' : ($v ? 'DA' : 'NE');
    $skupine = [
        'mejniElementi' => 'Mejni elementi',
        'povrsinskoPohistvo' => 'Površinsko pohištvo',
        'posamezniElementi' => 'Posamezni elementi in osebe',
        'absorberji' => 'Novi absorberji',
    ];
    $velicine = [
        'absorpcijskaPovrsina' => 'A skupaj z zrakom [m²]',
        'absorpcijaZraka' => 'A zraka [m²]',
        'alfa' => 'α mejnih površin',
        'en12354' => 'T EN 12354-6 [s]',
        'sabine' => 'T Sabine [s]',
        'eyring' => 'T Eyring [s]',
        'odmevniCas' => 'T izbrani [s]',
    ];
    ?>
<?php if (!($this instanceof \App\Core\PdfView)) { ?>
<p class="actions"><a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a></p>
<?php } ?>
<h1>Odmevni hrup: <?= h($prostor->id . ' – ' . $prostor->naziv) ?></h1>
<p>Prostornina: <?= $num($prostor->prostornina) ?> m³;
    prostornina zraka: <?= $num($prostor->pred->prostorninaZraka) ?> m³;
    mejne površine: <?= $num($prostor->pred->povrsinaMejnihElementov) ?> m².
    Zasedenost: <?= h($prostor->zasedenost) ?>.</p>
<?php foreach ($skupine as $skupina => $naslov) { ?>
    <?php if (!empty($prostor->{$skupina})) { ?>
<br />
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr><th colspan="10"><strong><?= h($naslov) ?></strong></th></tr>
    <tr><th width="28%">Element</th><th width="6%">Št.</th>
        <th width="14%">S pred / po [m²]</th><th width="10%">V [m³/kos]</th>
        <?php foreach ($frekvence as $f) {
            ?><th width="7%"><?= $f ?> Hz</th><?php
        } ?></tr>
    </thead>
        <?php foreach ($prostor->{$skupina} as $element) {
            $pred = ((array)$prostor->pred->elementi)[$element->id] ?? null;
            $po = ((array)$prostor->po->elementi)[$element->id] ?? null;
            $spekter = $element->alfa ?? $element->absorpcijskaPovrsina;
            ?>
    <tr><td><?= h($element->naziv) ?> (<?= h($element->id) ?>)
            <?php if (isset($element->idElementa)) {
                ?><br />Na: <?= h($element->idElementa) ?><?php
            } ?>
            <?php if (property_exists($element, 'idAbsorpcije')) {
                ?><br />Knjižnica: <?= h($element->idAbsorpcije) ?>
            <?php } elseif (property_exists($element, 'vir')) {
                ?><br />Vir: <?= h($element->vir) ?><?php
            } ?>
        <br /><?= isset($element->alfa) ? 'α' : 'A [m²/kos]' ?></td>
        <td><?= $element->stevilo ?></td>
        <td><?= $num($pred->povrsina ?? null) ?> / <?= $num($po->povrsina ?? null) ?></td>
        <td><?= $num($element->prostornina ?? 0) ?></td>
            <?php foreach ($frekvence as $f) {
                ?><td><?= $num($vrednost($spekter, $f)) ?></td><?php
            } ?>
    </tr>
        <?php } ?>
</table>
    <?php } ?>
<?php } ?>
<h2>Primerjava pred namestitvijo absorberjev in po njej</h2>
<table border="1" cellpadding="3" width="100%">
    <thead>
    <tr><th width="40%">Veličina</th><?php foreach ($frekvence as $f) {
        ?><th width="10%"><?= $f ?> Hz</th><?php
                                     } ?></tr>
    </thead>
    <?php foreach (['pred' => 'Pred', 'po' => 'Po'] as $kljuc => $naziv) { ?>
        <?php foreach ($velicine as $velicina => $opis) { ?>
    <tr><td><?= h($naziv . ': ' . $opis) ?></td><?php foreach ($frekvence as $f) {
        ?><td><?= $num($vrednost($prostor->{$kljuc}->{$velicina}, $f)) ?></td><?php
            } ?></tr>
        <?php } ?>
    <tr><td><?= h($naziv) ?>: izbrana metoda</td><?php foreach ($frekvence as $f) {
        ?><td><?= h($vrednost($prostor->{$kljuc}->metoda, $f)) ?></td><?php
            } ?></tr>
    <?php } ?>
    <tr><td>Znižanje odmevne komponente ΔL [dB]</td><?php foreach ($frekvence as $f) {
        ?><td><?= $num($vrednost($prostor->znizanjeHrupa, $f)) ?></td><?php
                                                    } ?></tr>
</table>
<p>Srednji T (500 in 1000 Hz): pred <?= $num($prostor->pred->srednjiOdmevniCas) ?> s;
    po <?= $num($prostor->po->srednjiOdmevniCas) ?> s.
    Cilj/meja = <?= $num($prostor->skladnost->mejnaVrednost ?? $prostor->skladnost->optimalniCas) ?> s.</p>
<h2>Preverjanje odmevnega časa</h2>
<p><?= h($prostor->skladnost->vir . '. ' . $prostor->skladnost->kriterij) ?></p>
<?php if (!empty((array)$prostor->skladnost->pasovi)) { ?>
<table border="1" cellpadding="3" width="100%">
    <tr><th>Frekvenca [Hz]</th><th>Spodnja meja [s]</th><th>Zgornja meja [s]</th><th>Ustreza</th></tr>
    <?php foreach ($prostor->skladnost->pasovi as $f => $pas) { ?>
    <tr><td><?= $f ?></td><td><?= $num($pas->min) ?></td><td><?= $num($pas->max) ?></td>
        <td><?= h($daNe($pas->ustreza)) ?></td></tr>
    <?php } ?>
</table>
<?php } ?>
<p><strong>USTREZNOST: <?= mb_strtoupper($daNe($prostor->skladnost->ustreza)) ?></strong></p>
<?php foreach ($prostor->skladnost->opombe as $opomba) {
    ?><p><?= h($opomba) ?></p><?php
} ?>
<p>»—« pomeni, da vrednost ni določljiva. Pri ničelni absorpciji odmevni čas ni končen.
    Presoja obravnava odmevni čas; ne predstavlja preverjanja vseh zahtev prostorske akustike.</p>
