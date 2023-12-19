<?php
declare(strict_types=1);

namespace App\Calc\Hrup\ZracniHrup\Izbire;

enum VrstaSpoja: string
{
    use \App\Lib\Traits\GetOrdinalTrait;

    case TogiKrizniSpoj = 'togiKrizni';
    case TogiTSpoj = 'togiT';
    case KrizniZElasticnimSpojem = 'krizniElasticni';
    case SpojZLahkoFasado = 'lahkaFasada';
    case SpojLahkeDvojneSteneSHomogenimElementom = 'dvojnaStenaSHomogenimElementom';
    case SpojPovezanihLahkihDvojnihSten = 'povezaniDvojniSteni';
    case KotniSpoj = 'kotni';
    case SpremembaDebeline = 'spremembaDebeline';

    /**
     * Izračun faktorjev K
     *
     * @param float $M Razmerje mas
     * @return array
     */
    public function faktorjiK($M)
    {
        $ret = [];
        switch ($this) {
            case self::TogiKrizniSpoj:
                $ret['K12'] = 8.7 + 5.7 * pow($M, 2);
                $ret['K13'] = 8.7 + 17.1 * $M + 5.7 * pow($M, 2);
                $ret['K14'] = $ret['K12'];
                $ret['K23'] = $ret['K12'];
                $ret['K24'] = $ret['K13'];
                $ret['K34'] = $ret['K12'];
                break;
            case self::TogiTSpoj:
                $ret['K12'] = 5.7 + 5.7 * pow($M, 2);
                $ret['K13'] = 5.7 + 14.1 * $M + 5.7 * pow($M, 2);
                $ret['K23'] = $ret['K12'];
                break;
            case self::KrizniZElasticnimSpojem:
                // Hz; if E1/t1 ~= 100 MN/m³
                $f1 = 125;
                $D1 = 10 * log10(500 / $f1);

                $ret['K12'] = 5.7 + 5.7 * pow($M, 2) + $D1;
                $ret['K13'] = 5.7 + 14.1 * $M + 5.7 * pow($M, 2) + 2 * $D1;
                $ret['K14'] = $ret['K12'];

                $ret['K23'] = $ret['K12'];
                $ret['K24'] = 5.7 + 5.7 * pow($M, 2);

                $ret['K34'] = $ret['K12'];
                break;
            case self::SpojZLahkoFasado:
                $ret['K12'] = 10 + 10 * abs($M);
                $ret['K13'] = 5 + 10 * $M;

                $ret['K23'] = $ret['K12'];
                break;
            case self::SpojLahkeDvojneSteneSHomogenimElementom:
                $f_k = 500;
                $f = 500;

                $ret['K12'] = 10 + 10 * abs($M) + 3.3 * log10($f / $f_k);
                $ret['K13'] = 10 + 20 * $M - 3.3 * log10($f / $f_k);
                if ($ret['K13'] < 10) {
                    $ret['K13'] = 10;
                }
                $ret['K14'] = $ret['K12'];

                $ret['K23'] = $ret['K12'];
                $ret['K24'] = 3 - 14.1 + 5.7 * pow($M, 2);

                $ret['K34'] = $ret['K12'];
                break;
            case self::SpojPovezanihLahkihDvojnihSten:
                $f_k = 500;
                $f = 500;

                $ret['K12'] = 10 + 20 * $M - 3.3 * log10($f / $f_k);
                $ret['K13'] = 10 + 10 * abs($M) + 3.3 * log10($f / $f_k);
                $ret['K14'] = $ret['K12'];

                $ret['K23'] = $ret['K12'];
                $ret['K24'] = $ret['K13'];

                $ret['K34'] = $ret['K12'];
                break;
            case self::KotniSpoj:
                $ret['K12'] = 15 * abs($M) - 3;
                if ($ret['K12'] < -2) {
                    $ret['K12'] = -2;
                }
                break;
            case self::SpremembaDebeline:
                $ret['K12'] = 5 * pow($M, 2) - 5;
                break;
        }

        return $ret;
    }

    /**
     * Vrne naziv spoja
     *
     * @return string
     */
    public function naziv()
    {
        $nazivi = ['Togi križni spoj', 'Togi "T" spoj', 'Križni spoj z elastičnim stikom', 'Spoj z lahko fasado',
            'Spoj lahke dvojne stene s homogenim elementom', 'Spoj dveh povezanih lahkih dvojnih sten',
            'Kotni spoj', 'Sprememba debeline'];

        return $nazivi[$this->getOrdinal()];
    }
}
