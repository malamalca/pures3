<?php
declare(strict_types=1);

namespace App\Lib\Traits;

trait GetOrdinalTrait
{
    /**
     * Vrne index elementa znotraj enuma
     *
     * @return int
     */
    public function getOrdinal(): int
    {
        $value = array_filter($this->cases(), fn($case) => $this->value == $case->value);

        if (!empty($value)) {
            return array_keys($value)[0];
        } else {
            return -1;
        }
    }
}
