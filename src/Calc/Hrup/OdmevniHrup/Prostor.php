<?php
declare(strict_types=1);

namespace App\Calc\Hrup\OdmevniHrup;

use App\Lib\EvalMath;
use JsonSchema\Validator;

class Prostor
{
    public const FREKVENCE = [125, 250, 500, 1000, 2000, 4000];

    public string $id;
    public string $naziv;
    public float $prostornina;
    public string $vrsta;
    public string $zasedenost;
    public string $metoda;
    public float $hitrostZvoka = 345.6;
    public ?\stdClass $mere = null;
    public ?string $nazivVrste = null;
    public float|array|null $mejniOdmevniCas = null;
    public array $mejniElementi = [];
    public array $povrsinskoPohistvo = [];
    public array $posamezniElementi = [];
    public array $absorberji = [];
    public array $absorpcijaZraka = [];
    public ?\stdClass $pred = null;
    public ?\stdClass $po = null;
    public array $znizanjeHrupa = [];
    public ?\stdClass $skladnost = null;

    /**
     * Vhodni podatki prostora, brez spreminjanja izvornega objekta.
     *
     * @param \stdClass|string $config Podatki prostora
     */
    public function __construct(\stdClass|string $config)
    {
        if (is_string($config)) {
            $config = json_decode($config, false, 512, JSON_THROW_ON_ERROR);
        }
        $validator = new Validator();
        $schema = json_decode((string)file_get_contents(SCHEMAS . 'Hrup' . DS . 'odmevniHrupSchema.json'));
        $vhod = [$config];
        $validator->validate($vhod, $schema);
        if (!$validator->isValid()) {
            $napake = array_map(fn($e) => $e['property'] . ': ' . $e['message'], $validator->getErrors());
            throw new \InvalidArgumentException('Odmevni hrup: ' . implode('; ', $napake));
        }

        $this->id = $config->id;
        $this->naziv = $config->naziv;
        $this->prostornina = $this->stevilo($config->prostornina);
        $this->vrsta = $config->vrsta;
        $this->zasedenost = $config->zasedenost;
        $this->metoda = $config->metoda ?? 'en12354';
        $this->hitrostZvoka = $config->hitrostZvoka ?? 345.6;
        // Preglednica 1: 20 °C, relativna vlažnost 50–70 %, enota Neper/m.
        $this->absorpcijaZraka = isset($config->absorpcijaZraka) ? $this->spekter($config->absorpcijaZraka)
            : array_combine(self::FREKVENCE, [0.0001, 0.0003, 0.0006, 0.001, 0.0017, 0.0041]);
        $this->nazivVrste = $config->nazivVrste ?? null;
        if (isset($config->mejniOdmevniCas)) {
            $this->mejniOdmevniCas = is_object($config->mejniOdmevniCas)
                ? $this->spekter($config->mejniOdmevniCas) : (float)$config->mejniOdmevniCas;
            foreach ((array)$this->mejniOdmevniCas as $meja) {
                if ($meja <= 0) {
                    throw new \InvalidArgumentException('Lastna meja odmevnega časa mora biti pozitivna.');
                }
            }
        }
        if (isset($config->mere)) {
            $this->mere = (object)array_map(fn($v) => $this->stevilo($v), (array)$config->mere);
            if (min((array)$this->mere) <= 0) {
                throw new \InvalidArgumentException('Mere prostora morajo biti pozitivne.');
            }
        }

        $ids = [];
        foreach (['mejniElementi', 'povrsinskoPohistvo', 'posamezniElementi', 'absorberji'] as $skupina) {
            foreach ($config->{$skupina} ?? [] as $vhodniElement) {
                /** @var \stdClass $element */
                $element = clone $vhodniElement;
                if (isset($element->idAbsorpcije)) {
                    $tip = match ($skupina) {
                        'posamezniElementi' => 'predmeti',
                        'povrsinskoPohistvo' => 'razporeditve',
                        default => 'povrsine',
                    };
                    /** @var \stdClass $zapis */
                    $zapis = Absorpcija::poisci($tip, $element->idAbsorpcije);
                    $polje = $skupina === 'posamezniElementi' ? 'absorpcijskaPovrsina' : 'alfa';
                    $element->{$polje} = $zapis->{$polje};
                    $element->vir = $zapis->vir;
                    $element->opisAbsorpcije = $zapis->naziv;
                    if (isset($zapis->vrsta)) {
                        $element->vrsta = $zapis->vrsta;
                    }
                }
                if (in_array($element->id, $ids, true)) {
                    throw new \InvalidArgumentException('Podvojen id elementa: ' . $element->id);
                }
                $ids[] = $element->id;
                foreach (['povrsina', 'prostornina'] as $velicina) {
                    if (isset($element->{$velicina})) {
                        $element->{$velicina} = $this->stevilo($element->{$velicina});
                    }
                }
                $element->stevilo = $element->stevilo ?? 1;
                if ($skupina == 'posamezniElementi') {
                    if (!empty($element->ocenaIzProstornine)) {
                        if (($element->vrsta ?? 'pohistvo') === 'osebe') {
                            throw new \InvalidArgumentException('Ocena iz prostornine velja le za trde predmete.');
                        }
                        $element->absorpcijskaPovrsina = $element->prostornina ** (2 / 3);
                        $element->vir = 'SIST EN 12354-6:2004, enačba (4), ocena trdega predmeta';
                    }
                    $element->absorpcijskaPovrsina = $this->spekter($element->absorpcijskaPovrsina);
                } else {
                    $element->alfa = $this->spekter($element->alfa);
                    $element->sipanje = $this->spekter($element->sipanje ?? 0);
                    if ($element->povrsina <= 0) {
                        throw new \InvalidArgumentException('Površina mora biti pozitivna: ' . $element->id);
                    }
                }
                $this->{$skupina}[] = $element;
            }
        }
        if ($this->prostornina <= 0) {
            throw new \InvalidArgumentException('Prostornina prostora mora biti pozitivna.');
        }
    }

    /**
     * Ovrednoti število ali izraz, kot v ostalih izračunih Hrup.
     *
     * @param float|int|string $vrednost Vhodna vrednost
     * @return float
     */
    private function stevilo(float|int|string $vrednost): float
    {
        $rezultat = is_string($vrednost) ? (new EvalMath())->e($vrednost) : $vrednost;
        if ($rezultat === false || !is_numeric($rezultat) || !is_finite((float)$rezultat) || $rezultat < 0) {
            throw new \InvalidArgumentException('Neveljavno nenegativno število ali izraz: ' . $vrednost);
        }

        return (float)$rezultat;
    }

    /**
     * Pretvori enotno vrednost ali oktavni spekter v tabelo.
     *
     * @param float|int|object $vrednost Vrednost po frekvencah
     * @return array
     */
    private function spekter(float|int|object $vrednost): array
    {
        $spekter = [];
        $vrednosti = is_object($vrednost) ? (array)$vrednost : [];
        foreach (self::FREKVENCE as $frekvenca) {
            $spekter[$frekvenca] = (float)(is_object($vrednost) ? $vrednosti[$frekvenca] : $vrednost);
        }

        return $spekter;
    }

    /**
     * Izračun obstoječega in obdelanega prostora.
     *
     * @return void
     */
    public function analiza(): void
    {
        $pokritePovrsine = [];
        $ploskve = array_merge($this->mejniElementi, $this->povrsinskoPohistvo);
        foreach ($this->absorberji as $absorber) {
            $ploskev = array_first_callback($ploskve, fn($p) => $p->id === $absorber->idElementa);
            if (!$ploskev) {
                throw new \InvalidArgumentException('Neznan nosilni element absorberja: ' . $absorber->idElementa);
            }
            $pokritePovrsine[$ploskev->id] = ($pokritePovrsine[$ploskev->id] ?? 0) + $absorber->povrsina;
            if ($pokritePovrsine[$ploskev->id] > $ploskev->povrsina * $ploskev->stevilo + 1e-9) {
                throw new \InvalidArgumentException('Absorberji presegajo površino elementa: ' . $ploskev->id);
            }
        }
        $this->pred = $this->izracunStanja([]);
        $this->po = $this->izracunStanja($pokritePovrsine, true);
        foreach (self::FREKVENCE as $frekvenca) {
            $t1 = $this->pred->odmevniCas[$frekvenca];
            $t2 = $this->po->odmevniCas[$frekvenca];
            $this->znizanjeHrupa[$frekvenca] = $t1 !== null && $t2 !== null && $t1 > 0 && $t2 > 0
                ? 10 * log10($t1 / $t2) : null;
        }
        $this->skladnost = (new Zahteve())->preveri($this);
        $this->skladnost->opombe = array_merge($this->skladnost->opombe, $this->po->opombe);
        if (!$this->po->modelUporaben) {
            $this->skladnost->ustreza = null;
            foreach ($this->skladnost->pasovi as $pas) {
                $pas->ustreza = null;
            }
        }
    }

    /**
     * Absorpcija vseh treh skupin, zraka in odmevni čas po oktavah.
     *
     * @param array $pokritePovrsine Vsota površin absorberjev po nosilnih elementih
     * @param bool $zAbsorberji Dodaj absorpcijo novih oblog
     * @return \stdClass
     */
    private function izracunStanja(array $pokritePovrsine, bool $zAbsorberji = false): \stdClass
    {
        $v = $this->prostornina;
        foreach (array_merge($this->posamezniElementi, $this->povrsinskoPohistvo) as $element) {
            $v -= ($element->prostornina ?? 0) * $element->stevilo;
        }
        if ($v <= 0) {
            throw new \InvalidArgumentException('Pohištvo in osebe izpodrinejo celotno prostornino prostora.');
        }
        $s = array_sum(array_map(fn($e) => $e->povrsina * $e->stevilo, $this->mejniElementi));
        $stanje = (object)[
            'prostorninaZraka' => $v, 'povrsinaMejnihElementov' => $s, 'elementi' => [],
            'absorpcijskaPovrsina' => [], 'absorpcijaZraka' => [], 'alfa' => [],
            'sabine' => [], 'eyring' => [], 'en12354' => [], 'odmevniCas' => [], 'metoda' => [],
            'delezProstorninePredmetov' => 1 - $v / $this->prostornina,
            'modelUporaben' => true, 'opombe' => [],
        ];
        foreach (self::FREKVENCE as $frekvenca) {
            $ameja = 0;
            $apohistvo = 0;
            foreach (['mejniElementi', 'povrsinskoPohistvo', 'posamezniElementi'] as $skupina) {
                foreach ($this->{$skupina} as $element) {
                    $povrsina = $skupina == 'posamezniElementi' ? null : max(
                        0,
                        $element->povrsina * $element->stevilo - ($pokritePovrsine[$element->id] ?? 0)
                    );
                    $a = $povrsina === null ? $element->absorpcijskaPovrsina[$frekvenca] * $element->stevilo
                        : $povrsina * $element->alfa[$frekvenca];
                    if ($skupina == 'mejniElementi') {
                        $ameja += $a;
                    } else {
                        $apohistvo += $a;
                    }
                    $stanje->elementi[$element->id]['povrsina'] = $povrsina;
                    $stanje->elementi[$element->id]['absorpcijskaPovrsina'][$frekvenca] = $a;
                }
            }
            if ($zAbsorberji) {
                foreach ($this->absorberji as $absorber) {
                    $a = $absorber->povrsina * $absorber->alfa[$frekvenca];
                    if (array_first_callback($this->mejniElementi, fn($e) => $e->id === $absorber->idElementa)) {
                        $ameja += $a;
                    } else {
                        $apohistvo += $a;
                    }
                    $stanje->elementi[$absorber->id]['povrsina'] = $absorber->povrsina;
                    $stanje->elementi[$absorber->id]['absorpcijskaPovrsina'][$frekvenca] = $a;
                }
            }
            $alfa = min(1, $ameja / $s);
            $azrak = 4 * $this->absorpcijaZraka[$frekvenca] * $v;
            $a = $ameja + $apohistvo + $azrak;
            $aey = $alfa < 1 ? -$s * log1p(-$alfa) + $apohistvo + $azrak : INF;
            $stanje->absorpcijskaPovrsina[$frekvenca] = $a;
            $stanje->absorpcijaZraka[$frekvenca] = $azrak;
            $stanje->alfa[$frekvenca] = $alfa;
            $stanje->sabine[$frekvenca] = $a > 0 ? 0.163 * $v / $a : null;
            $stanje->eyring[$frekvenca] = $aey > 0 ? 0.163 * $v / $aey : null;
            $stanje->en12354[$frekvenca] = $a > 0 ? 55.3 / $this->hitrostZvoka * $v / $a : null;
            $metoda = $this->metoda == 'samodejno' ? ($alfa < 0.2 ? 'sabine' : 'eyring') : $this->metoda;
            $stanje->metoda[$frekvenca] = $metoda;
            $stanje->odmevniCas[$frekvenca] = $metoda === 'en12354D2' ? null : $stanje->{$metoda}[$frekvenca];
        }
        if ($this->metoda === 'en12354D2') {
            $stanje->d2 = (new ModelD2())->izracun($this, $stanje, $zAbsorberji);
            $stanje->odmevniCas = $stanje->d2->odmevniCas;
        }
        if ($stanje->delezProstorninePredmetov >= 0.2) {
            $stanje->modelUporaben = false;
            $stanje->opombe[] = 'Prostorninski delež predmetov je vsaj 0,2; potrebna je obravnava po dodatku D.3.';
        }
        if ($this->mere !== null && min((array)$this->mere) > 0) {
            if (max((array)$this->mere) > 5 * min((array)$this->mere)) {
                $stanje->modelUporaben = false;
                $stanje->opombe[] = 'Razmerje mer presega 5; model za odmevni čas ni uporaben (4.6, D.3).';
            }
        } else {
            $stanje->opombe[] = 'Mere niso podane; geometrijske omejitve modela niso preverjene.';
        }
        if ($this->metoda !== 'en12354D2') {
            $stanje->opombe[] = 'Osnovni model predpostavlja difuzno polje in porazdeljeno absorpcijo (4.1, 4.6).';
            $this->preveriPorazdelitevAbsorpcije($stanje, $zAbsorberji);
        }
        /** @var array<int, float|null> $t */
        $t = $stanje->odmevniCas;
        $stanje->srednjiOdmevniCas = $t[500] !== null && $t[1000] !== null ? ($t[500] + $t[1000]) / 2 : null;

        return $stanje;
    }

    /**
     * Preveri omejitev faktorja 3 med nasprotnimi ploskvami iz točke 4.6.
     *
     * @param \stdClass $stanje Izračunano stanje
     * @param bool $zAbsorberji Ali so absorberji vključeni
     * @return void
     */
    private function preveriPorazdelitevAbsorpcije(\stdClass $stanje, bool $zAbsorberji): void
    {
        $povrsine = $absorpcija = [];
        foreach ($this->mejniElementi as $element) {
            /** @var \stdClass $element */
            if (!isset($element->ploskev)) {
                return;
            }
            $ploskev = $element->ploskev;
            $povrsine[$ploskev] = ($povrsine[$ploskev] ?? 0) + $stanje->elementi[$element->id]['povrsina'];
            foreach (self::FREKVENCE as $frekvenca) {
                $absorpcija[$ploskev][$frekvenca] = ($absorpcija[$ploskev][$frekvenca] ?? 0)
                    + $stanje->elementi[$element->id]['absorpcijskaPovrsina'][$frekvenca];
            }
        }
        if ($zAbsorberji) {
            foreach ($this->absorberji as $absorber) {
                $nosilec = array_first_callback($this->mejniElementi, fn($e) => $e->id === $absorber->idElementa);
                if (!$nosilec) {
                    continue;
                }
                $ploskev = $nosilec->ploskev;
                $povrsine[$ploskev] = ($povrsine[$ploskev] ?? 0) + $absorber->povrsina;
                foreach (self::FREKVENCE as $frekvenca) {
                    $absorpcija[$ploskev][$frekvenca] = ($absorpcija[$ploskev][$frekvenca] ?? 0)
                        + $stanje->elementi[$absorber->id]['absorpcijskaPovrsina'][$frekvenca];
                }
            }
        }
        foreach ([['x0', 'x1'], ['y0', 'y1'], ['z0', 'z1']] as [$prva, $druga]) {
            if (!isset($povrsine[$prva], $povrsine[$druga])) {
                return;
            }
            foreach (self::FREKVENCE as $frekvenca) {
                $a1 = $absorpcija[$prva][$frekvenca] / $povrsine[$prva];
                $a2 = $absorpcija[$druga][$frekvenca] / $povrsine[$druga];
                if (min($a1, $a2) == 0 ? max($a1, $a2) > 0 : max($a1, $a2) / min($a1, $a2) > 3) {
                    $stanje->modelUporaben = false;
                    $stanje->opombe[] = 'Absorpcija nasprotnih ploskev se razlikuje za več kot faktor 3; ' .
                        'uporabi model dodatka D.2.';

                    return;
                }
            }
        }
    }

    /**
     * Izvoz rezultatov, vključno s polji obstoječega izkaza.
     *
     * @return \stdClass
     */
    public function export(): \stdClass
    {
        $rezultat = (object)get_object_vars($this);
        $rezultat->enota = 'T [s]';
        $rezultat->projektnaVrednost = $this->skladnost->mejnaVrednost ?? $this->skladnost->optimalniCas ?? null;
        $rezultat->izracunanaVrednost = $this->po->srednjiOdmevniCas ?? null;

        return $rezultat;
    }
}
