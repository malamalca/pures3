<tr>
    <td><?= $niz->naziv ?></td>
    <?= implode(PHP_EOL, array_map(fn($v) => '<td class="center w-6">' . $this->numFormat($v, $niz->decimalke ?? 1) . '</td>', $niz->vrednosti)) ?>
    <th class="right w-6"><?= !empty($niz->vsota) ? $this->numFormat(array_sum($niz->vrednosti), $niz->decimalke ?? 1) : '&nbsp;' ?></th>
</tr>