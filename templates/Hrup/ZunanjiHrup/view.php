<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Zunanji hrup</h1>

<p class="actions">
    <a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<table>
    <tr>
        <td colspan="2">Id prostora</td>
        <td colspan="2" class="left strong"><?= h($prostor->id) ?></td>
    </tr>
    <tr>
        <td colspan="2">Naziv prostora</td>
        <td colspan="2" class="left strong"><?= h($prostor->naziv) ?></td>
    </tr>
</table>
