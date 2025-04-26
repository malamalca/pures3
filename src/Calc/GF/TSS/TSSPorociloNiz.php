<?php
declare(strict_types=1);

namespace App\Calc\GF\TSS;

class TSSPorociloNiz implements \JsonSerializable
{
    /**
     * Class Constructor
     *
     * @param string $id ID
     * @param string $naziv Naziv
     * @param string $opis Opis
     * @param array $vrednosti Vrednosti
     * @param int $decimalke Število decimalk
     * @param bool $vsota Prikaži vsoto niza
     * @return void
     */
    public function __construct(
        public string $id,
        public string $naziv,
        public string $opis,
        public $vrednosti,
        public int $decimalke = 1,
        public bool $vsota = true
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
