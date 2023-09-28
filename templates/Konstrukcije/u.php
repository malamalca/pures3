<?php
    use App\Core\App;
?>
<p>
    <form method="post">
        <label for="data">Podatki JSON:</label>
        <textarea name="data" style="width: 100%"><?= h($data ?? '') ?></textarea>
        <button type="submit">Pošlji</button>
    </form>
</p>
<?php
    if (!empty($kons)) {
?>

<h3>Prikaz temperature v konstrukciji</h3>
<?php
    $mesec = 0;

    $thicknesses = [];
    $temperatures = [];
    $layers = [];

    $temperatures[] = $okolje->notranjaT[$mesec];
    $temperatures[] = $kons->Tsi[$mesec];

    foreach ($kons->materiali as $i => $material) {
        $temperatures[] = $material->T[$mesec];
        $thicknesses[] = $material->debelina;
        $layers[] = $material->opis;
        //foreach ($material->racunskiSloji as $k => $sloj) {
        //    $temperatures[] = $sloj->T[$mesec];
        //    $thicknesses[] = $sloj->debelina;
        //    $layers[] = $sloj->opis;
        //}
    }

    $temperatures[] = $kons->Tse[$mesec];
    $temperatures[] = $okolje->zunanjaT[$mesec];

    $temperatures = array_map(fn($v) => sprintf('data[]=%0.2f', $v), $temperatures);
    $thicknesses = array_map(fn($v) => sprintf('thickness[]=%0.2f', $v), $thicknesses);
    $layers = array_map(fn($v) => sprintf('layer[]=%s', $v), $layers);
?>

<img src="<?= App::url(sprintf('/konstrukcije/graf.gif?%1$s', implode('&', array_merge($temperatures, $thicknesses, $layers)))) ?>" />

<?php
    }
?>