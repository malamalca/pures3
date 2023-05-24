<?php
namespace App\Calc\TSS\Razvodi;

class ElementRazvoda {
    public VrstaRazvodnihCevi $vrsta;
    public VrstaIzolacijeCevi $izolacija;

    private ?float $toplotnaPrevodnost = null;
    private ?float $delezVOgrevaniConi = null;

    public function __construct(VrstaRazvodnihCevi $vrsta, $config = null)
    {
        $this->vrsta = $vrsta;
        if ($config) {
            $this->parseConfig($config);
        }
    }

    public function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        $this->izolacija = VrstaIzolacijeCevi::from($config->izolacija ?? 'izolirane');
        $this->toplotnaPrevodnost = $config->Ucevi ?? null;
        $this->delezVOgrevaniConi = $config->delezVOgrevaniConi ?? $this->privzetiDelezVOgrevaniConi();
    }

    public function racunskaToplotnaPrevodnost($povrsinaCone)
    {
        $zaPovrsino = $povrsinaCone <= 200 ? 1 : ($povrsinaCone > 500 ? 3 : 2);

        $vrednostiU = [
            VrstaRazvodnihCevi::HorizontalniRazvod->value => [0.3, $zaPovrsino, $zaPovrsino, $zaPovrsino, $zaPovrsino],
            VrstaRazvodnihCevi::DvizniVod->value => [0.3, 0.75, 1.35, $zaPovrsino, $zaPovrsino],
            VrstaRazvodnihCevi::PrikljucniVod->value => [0.3, $zaPovrsino, $zaPovrsino, $zaPovrsino, $zaPovrsino],
        ];

        return $vrednostiU[$this->vrsta->value][$this->izolacija->getOrdinal()];
    }

    public function privzetiDelezVOgrevaniConi()
    {
        switch ($this->vrsta) {
            case VrstaRazvodnihCevi::DvizniVod:
                $deleziPoVrstiIzolacije = [1, 0.73, 0.59, 1, 1];
                return $deleziPoVrstiIzolacije[$this->izolacija->getOrdinal()];
            default:
                return 1;
        }
    }

    public function toplotneIzgube($razvod, $cona, $znotrajOvoja)
    {
        $U = $this->toplotnaPrevodnost ?? $this->racunskaToplotnaPrevodnost($cona->sirina * $cona->dolzina);
        $dolzina = $razvod->dolzinaCevi($this->vrsta, $cona);

        return $U * $dolzina * ($znotrajOvoja ? $this->delezVOgrevaniConi : (1 - $this->delezVOgrevaniConi));
    }
}