<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

class TSSPorociloPodatek implements \JsonSerializable
{
    /**
     * Class Constructor
     *
     * @param string $naziv Naziv
     * @param string $opis Opis
     * @param string|int|float $vrednost Vrednost
     * @param string $enota Enota
     * @param int $decimalke Število decimalk
     * @return void
     */
    public function __construct(
        public string $naziv,
        public string $opis,
        public $vrednost,
        public string $enota,
        public int $decimalke = 1
    ) {
    }

    /**
     * Json serializer
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
