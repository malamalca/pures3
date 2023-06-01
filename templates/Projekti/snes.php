<?php
    use App\Core\App;
    use App\Lib\Calc;
?>
<h1>Analiza sNES "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>
<p>
<a class="button" href="<?= App::url('/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<table border="1">
    <tr>
        <td colspan="4"><h2>Kazalniki energijske učinkovitosti stavbe</h2></td>
    </tr>
    <tr>
        <td>Neutežena dovedena energija za delovanje TSS </td>
        <td>E<sub>del,an</sub></td>
        <td>kWh/an</td>
    </tr>
</table>