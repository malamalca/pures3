<?php
    use App\Core\App;
?>
<h1>Seznam projektov</h1>

<p>
    <?= implode(PHP_EOL, array_map(fn($dir) => sprintf('<a href="%1$s">%2$s</a><br />', App::url('/hrup/projekti/view/' . $dir), $dir), $dirs)) ?>
</p>