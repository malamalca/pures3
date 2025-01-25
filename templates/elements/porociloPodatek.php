<tr>
    <td><?= h($podatek->opis) ?>:</td>
    <td><?= $podatek->naziv ?></td>
    <td><?= is_string($podatek->vrednost) ? h($podatek->vrednost) : $this->numFormat($podatek->vrednost, $podatek->decimalke ?? 1) ?></td>
    <td><?= $podatek->enota ?></td>
</tr>