<?php
declare(strict_types=1);

namespace App\Calc\Hrup\Elementi\Izbire;

enum VrstaDodatnegaSloja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case Pritrjen = 'pritrjen';
    case Elasticen = 'elasticen';
    case Nepritrjen = 'nepritrjen';

    /**
     * Izračun dRw
     *
     * @param float $povrsinskaMasaKonstrukcije Površina osnovne konstrukcije
     * @param float $RwKonstrukcije Rw osnovne konstrukcije
     * @param float $povrsinskaMasaSloja Površinska masa dodatnega sloja
     * @param float $dinamicnaTogost Dinamična togost dodatnega sloja pri vrsti "Elasticen"
     * @param float $sirinaMedprostora Sirina medprostora pri vrsti "Nepritrjen"
     * @return float
     */
    public function dRw(
        float $povrsinskaMasaKonstrukcije = 0,
        float $RwKonstrukcije = 0,
        float $povrsinskaMasaSloja = 0,
        float $dinamicnaTogost = 0,
        float $sirinaMedprostora = 0
    ) {
        $ret = 0;
        $f0 = 0;
        if ($this == VrstaDodatnegaSloja::Elasticen) {
            $f0 = 160 * sqrt(
                $dinamicnaTogost *
                (1 / $povrsinskaMasaKonstrukcije + 1 / $povrsinskaMasaSloja)
            );
        }
        if ($this == VrstaDodatnegaSloja::Nepritrjen) {
            $f0 = 160 * sqrt(
                0.111 / $sirinaMedprostora *
                (1 / $povrsinskaMasaKonstrukcije + 1 / $povrsinskaMasaSloja)
            );
        }

        if ($this == VrstaDodatnegaSloja::Elasticen || $this == VrstaDodatnegaSloja::Nepritrjen) {
            if ($f0 < 80) {
                $ret = 35 - $RwKonstrukcije / 2;
                if ($ret < 0) {
                    $ret = 0;
                }
            } elseif ($f0 < 112.5) {
                $ret = 32 - $RwKonstrukcije / 2;
                if ($ret < 0) {
                    $ret = 0;
                }
            } elseif ($f0 < 142.5) {
                $ret = 30 - $RwKonstrukcije / 2;
                if ($ret < 0) {
                    $ret = 0;
                }
            } elseif ($f0 < 180) {
                $ret = 28 - $RwKonstrukcije / 2;
                if ($ret < 0) {
                    $ret = 0;
                }
            } elseif ($f0 < 225) {
                $ret = -1;
            } elseif ($f0 < 282.5) {
                $ret = -3;
            } elseif ($f0 < 357.5) {
                $ret = -5;
            } elseif ($f0 < 450) {
                $ret = -7;
            } elseif ($f0 < 630) {
                $ret = -9;
            } elseif ($f0 < 1600) {
                $ret = -10;
            } else {
                $ret = -5;
            }
        }

        return $ret;
    }

    /**
     * Vrne naziv dodatnega sloja
     *
     * @return string
     */
    public function naziv()
    {
        $nazivi = ['Dodatek masivni konstrukciji', 'Sloj na elastični podlagi', 'Sloj z medprostorom'];

        return $nazivi[$this->getOrdinal()];
    }
}
