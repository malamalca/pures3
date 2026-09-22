<?php
declare(strict_types=1);

namespace App\Calc\Hrup\OdmevniHrup;

class Absorpcija
{
    /**
     * Knjižnica značilnih vrednosti iz preglednic B.1, C.1 in C.2.
     *
     * @return \stdClass
     */
    public static function knjiznica(): \stdClass
    {
        return json_decode((string)file_get_contents(CONFIG . 'HrupAbsorpcija.json'), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Vrne neodvisno kopijo vnosa z izvorom podatkov.
     *
     * @param string $skupina Skupina površin, predmetov ali razporeditev
     * @param string $id Id v knjižnici
     * @return \stdClass
     */
    public static function poisci(string $skupina, string $id): \stdClass
    {
        $knjiznica = self::knjiznica();
        $element = array_first_callback($knjiznica->{$skupina} ?? [], fn($e) => $e->id === $id);
        if (!$element) {
            throw new \InvalidArgumentException('Neznana absorpcija v skupini ' . $skupina . ': ' . $id);
        }

        return $element;
    }
}
