<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

abstract class TSSSistem
{
    public ?string $id;
    public bool $referencnaStavba = false;

    public array $porociloNizi = [];
    public array $porociloPodatki = [];
}
