<?php
    use App\Lib\Calc;
?>
<h1>Analiza sNES "<?= h($splosniPodatki->stavba->naziv) ?>"</h1>


<table border="1">
    <tr>
        <td colspan="4"><h2>Kazalniki energijske učinkovitosti stavbe</h2></td>
    </tr>
    <tr>
        <td>Neutežena dovedena energija za delovanje TSS </td>
        <td>E<sub>del,an</sub></td>
        <td class="center"><?= $this->numFormat(array_reduce($sistemi, function ($sum, $sistem) { $sum = ($sum ?? 0) + array_sum($sistem->potrebnaEnergija); return $sum; }), 1) ?></td>
        <td>kWh/an</td>
    </tr>
</table>