<?php
    use App\Core\App;
?>
<p>
    <form method="post">
        <label for="data">Podatki JSON:</label>
        <textarea name="data" style="width: 100%"><?= h($data ?? '') ?></textarea>
    </form>
</p>
<p>
    <img src="<?= App::url('/konstrukcije/graf.gif') ?>" />
</p>