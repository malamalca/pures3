<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone;

use App\Calc\GF\Cone\ElementiOvoja\NetransparentenElementOvoja;
use App\Calc\GF\Cone\ElementiOvoja\TransparentenElementOvoja;
use App\Calc\GF\Cone\Izbire\VrstaIzpostavljenostiFasad;
use App\Calc\GF\Cone\Izbire\VrstaLegeStavbe;
use App\Calc\GF\Cone\KlasifikacijeCone\KlasifikacijaCone;
use App\Calc\GF\Cone\KlasifikacijeCone\KlasifikacijaConeFactory;
use App\Calc\GF\TSS\Razsvetljava\Razsvetljava;
use App\Core\Log;
use App\Lib\Calc;
use App\Lib\EvalMath;

class Cona
{
    public string $id;
    public string $naziv;
    public KlasifikacijaCone $klasifikacija;
    public array $options = [];

    public float $brutoProstornina = 0;
    public float $netoProstornina = 0;
    public float $ogrevanaPovrsina = 0;
    public float $dolzina = 0;
    public float $sirina = 0;
    public float $etaznaVisina = 0;
    public int $steviloEtaz = 0;

    public float $deltaPsi = 0;

    public float $volumenZrakaOgrevanje = 0;
    public float $volumenZrakaHlajenje = 0;

    public float $notranjaTOgrevanje;
    public float $notranjaTHlajenje;

    public float $toplotnaKapaciteta = 0;

    public float $povrsinaOvoja = 0;
    public float $transparentnaPovrsina = 0;
    public float $faktorOblike = 0;

    public \stdClass $infiltracija;
    public \stdClass $notranjiViri;
    public \stdClass $razsvetljava;
    public \stdClass $prezracevanje;
    public \stdClass $ovoj;
    public ?\stdClass $TSV = null;
    public ?\stdClass $uravnavanjeVlage = null;

    public array $deltaTOgrevanje = [];
    public array $deltaTHlajenje = [];

    public array $transIzgubeOgrevanje = [];
    public array $transIzgubeHlajenje = [];

    public array $prezracevalneIzgubeOgrevanje = [];
    public array $prezracevalneIzgubeHlajenje = [];

    public array $solarniDobitkiOgrevanje = [];
    public array $solarniDobitkiHlajenje = [];

    public array $notranjiViriOgrevanje = [];
    public array $notranjiViriHlajenje = [];

    public array $vrnjeneIzgubeVOgrevanje = [];
    public array $vrnjeneIzgubeVTSV = [];

    public array $energijaOgrevanje = [];
    public float $skupnaEnergijaOgrevanje = 0;

    public array $energijaHlajenje = [];
    public float $skupnaEnergijaHlajenje = 0;

    public array $energijaTSV = [];
    public float $skupnaEnergijaTSV = 0;

    public array $energijaRazsvetljava = [];
    public float $skupnaEnergijaRazsvetljava = 0;

    public array $energijaNavlazevanje = [];
    public float $skupnaEnergijaNavlazevanje = 0;

    public array $energijaRazvlazevanje = [];
    public float $skupnaEnergijaRazvlazevanje = 0;

    public float $specTransmisijskeIzgube = 0;
    public float $specVentilacijskeIzgube = 0;

    public float $specLetnaToplota = 0;
    public float $specLetniHlad = 0;

    public float $specKoeficientTransmisijskihIzgub = 0;
    public float $dovoljenSpecKoeficientTransmisijskihIzgub = 0;

    public float $Htr_ogrevanje = 0;
    public float $Hgr_ogrevanje = 0;
    public float $Hve_ogrevanje = 0;

    public float $Htr_hlajenje = 0;
    public float $Hgr_hlajenje = 0;
    public float $Hve_hlajenje = 0;

    public array $ucinekDobitkov = [];
    public array $ucinekPonorov = [];

    private \stdClass $konstrukcije;

    /**
     * Class Constructor
     *
     * @param \stdClass|null $konstrukcije Seznam konstrukcij
     * @param \stdClass|string $config Configuration
     * @param array $options Možnosti izračuna
     * @return void
     */
    public function __construct($konstrukcije = null, $config = null, $options = [])
    {
        $this->options = $options;

        if ($konstrukcije) {
            $this->konstrukcije = $konstrukcije;
        } else {
            $this->konstrukcije = new \stdClass();
            $this->konstrukcije->transparentne = [];
            $this->konstrukcije->netransparentne = [];
        }

        if ($config) {
            $this->parseConfig($config);
        }
    }

    /**
     * Loads configuration from json|stdClass
     *
     * @param string|\stdClass $config Configuration
     * @return void
     */
    protected function parseConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config);
        }

        if (!empty($this->options['referencnaStavba'])) {
            // todo: za rekonstrukcije n50=2
            $config->infiltracija->n50 = 1.5;
            $config->toplotnaKapaciteta = 260000;
            $config->deltaPsi = 0;

            $prezracevanje = $config->prezracevanje ?? new \stdClass();
            $prezracevanje->vrsta = 'rekuperacija';
            $prezracevanje->regulacija = 'brez';
            $prezracevanje->izkoristek = 0.65;
            $config->prezracevanje = $prezracevanje;

            $razsvetljava = $config->razsvetljava ?? new \stdClass();
            unset($razsvetljava->mocSvetilk);
            $razsvetljava->faktorNaravneOsvetlitve = 1;
            $razsvetljava->faktorZmanjsanjaSvetlobnegaToka = 1;
            $razsvetljava->faktorPrisotnosti = 1;
            $razsvetljava->faktorDnevneSvetlobe = 0;
            $razsvetljava->osvetlitevDelovnePovrsine = 300;
            // velja za LED
            $razsvetljava->ucinkovitostViraSvetlobe = 80;
            $razsvetljava->faktorVzdrzevanja = 1;

            $config->razsvetljava = $razsvetljava;
        }

        $EvalMath = EvalMath::getInstance(['decimalSeparator' => '.', 'thousandsSeparator' => '']);

        $reflect = new \ReflectionClass(Cona::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            switch ($prop->getName()) {
                case 'klasifikacija':
                    $this->klasifikacija = KlasifikacijaConeFactory::create($config->klasifikacija);
                    break;
                case 'infiltracija':
                    if (isset($config->infiltracija)) {
                        $this->infiltracija = $config->infiltracija;

                        if (isset($this->infiltracija->lega) && is_int($this->infiltracija->lega)) {
                            // združljivosti za nazaj
                            $vrsteLege = VrstaLegeStavbe::cases();
                            $this->infiltracija->lega = $vrsteLege[$this->infiltracija->lega - 1];
                        } else {
                            $this->infiltracija->lega = gettype($config->infiltracija->lega) == 'object' &&
                                get_class($config->infiltracija->lega) == VrstaLegeStavbe::class ?
                                    $config->infiltracija->lega :
                                    VrstaLegeStavbe::from($config->infiltracija->lega ?? 'izpostavljena');
                        }

                        if (isset($this->infiltracija->zavetrovanost) && is_int($this->infiltracija->zavetrovanost)) {
                            // združljivosti za nazaj
                            $vrsteZavetrovanosti = VrstaIzpostavljenostiFasad::cases();
                            $this->infiltracija->zavetrovanost =
                                $vrsteZavetrovanosti[$this->infiltracija->zavetrovanost - 1];
                        } else {
                            $this->infiltracija->zavetrovanost =
                                gettype($config->infiltracija->zavetrovanost) == 'object' &&
                                get_class($config->infiltracija->zavetrovanost) == VrstaIzpostavljenostiFasad::class ?
                                $config->infiltracija->zavetrovanost :
                                VrstaIzpostavljenostiFasad::from(
                                    $config->infiltracija->zavetrovanost ?? 'izpostavljena'
                                );
                        }
                    }
                    break;
                case 'ovoj':
                    $this->ovoj = new \stdClass();
                    $this->ovoj->netransparentneKonstrukcije = [];
                    $this->ovoj->transparentneKonstrukcije = [];

                    $options = [];
                    if (!empty($this->options['referencnaStavba'])) {
                        $options['referencnaStavba'] = true;
                    }

                    if (!empty($config->ovoj->netransparentneKonstrukcije)) {
                        foreach ($config->ovoj->netransparentneKonstrukcije as $konsConfig) {
                            // poišči konstrukcijo v knjižnici
                            $kons = array_first(
                                $this->konstrukcije->netransparentne,
                                fn($k) => $k->id == $konsConfig->idKonstrukcije
                            );

                            $additionalOptions = [];
                            if (isset($konsConfig->idKonstrukcijeTla)) {
                                $additionalOptions['idKonstrukcijeTla'] = array_first(
                                    $this->konstrukcije->netransparentne,
                                    fn($k) => $k->id == $konsConfig->idKonstrukcijeTla
                                );
                            }
                            if (isset($konsConfig->idKonstrukcijeStene)) {
                                $additionalOptions['idKonstrukcijeStene'] = array_first(
                                    $this->konstrukcije->netransparentne,
                                    fn($k) => $k->id == $konsConfig->idKonstrukcijeStene
                                );
                            }
                            $this->ovoj->netransparentneKonstrukcije[] =
                                new NetransparentenElementOvoja(
                                    $kons,
                                    $konsConfig,
                                    array_merge($options, $additionalOptions)
                                );
                        }
                    }
                    if (!empty($config->ovoj->transparentneKonstrukcije)) {
                        foreach ($config->ovoj->transparentneKonstrukcije as $konsConfig) {
                            $kons = array_first(
                                $this->konstrukcije->transparentne,
                                fn($k) => $k->id == $konsConfig->idKonstrukcije
                            );

                            // določi netransparentni element v katerega je okno/vrata vgrajeno
                            $additionalOptions = [];
                            if (isset($konsConfig->idElementaVgradnje)) {
                                $elementVgradnje = array_first(
                                    $this->ovoj->netransparentneKonstrukcije,
                                    fn($k) => $k->id == $konsConfig->idElementaVgradnje
                                );
                                if ($elementVgradnje) {
                                    $additionalOptions['elementVgradnje'] = $elementVgradnje;
                                }
                            }

                            $tKons = new TransparentenElementOvoja(
                                $kons,
                                $konsConfig,
                                array_merge($options, $additionalOptions)
                            );
                            $this->ovoj->transparentneKonstrukcije[] = $tKons;

                            // če se okno vgrajuje v NT element, odštejem površino okna
                            if (!empty($elementVgradnje)) {
                                $elementVgradnje->povrsina -= $tKons->povrsina;
                            }
                        }
                    }
                    break;
                case 'prezracevanje':
                    if (isset($config->prezracevanje)) {
                        $this->prezracevanje = $config->prezracevanje;
                        if (isset($this->prezracevanje->volumenDovedenegaZraka)) {
                            if (
                                isset($this->prezracevanje->volumenDovedenegaZraka->ogrevanje) &&
                                is_string($this->prezracevanje->volumenDovedenegaZraka->ogrevanje)
                            ) {
                                $this->prezracevanje->volumenDovedenegaZraka->ogrevanje =
                                    (float)$EvalMath->e($this->prezracevanje->volumenDovedenegaZraka->ogrevanje);
                            }
                            if (
                                isset($this->prezracevanje->volumenDovedenegaZraka->hlajenje) &&
                                is_string($this->prezracevanje->volumenDovedenegaZraka->hlajenje)
                            ) {
                                $this->prezracevanje->volumenDovedenegaZraka->hlajenje =
                                    (float)$EvalMath->e($this->prezracevanje->volumenDovedenegaZraka->hlajenje);
                            }
                        }
                    }
                    break;
                default:
                    if (isset($config->{$prop->getName()})) {
                        $configValue = $config->{$prop->getName()};
                        if (
                            $prop->isInitialized($this) &&
                            in_array(gettype($this->{$prop->getName()}), ['double', 'int']) &&
                            gettype($configValue) == 'string'
                        ) {
                            $configValue = (float)$EvalMath->e($configValue);
                        }
                        $this->{$prop->getName()} = $configValue;
                    }
            }
        }
    }

    /**
     * Glavna funkcija za analizo cone
     *
     * @param \stdClass $okolje Podatki okolja
     * @return void
     */
    public function analiza($okolje)
    {
        Log::info(sprintf(
            '"%s": Začetek analize cone' . (empty($this->options['referencnaStavba']) ? '' : ' :: REF'),
            $this->id
        ));

        // izračunaj delto temperature med notranjostjo in zuanjim zrakom
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $this->deltaTOgrevanje[$mesec] = $this->notranjaTOgrevanje - $okolje->zunanjaT[$mesec];
            $this->deltaTHlajenje[$mesec] = $this->notranjaTHlajenje - $okolje->zunanjaT[$mesec];

            $this->transIzgubeOgrevanje[$mesec] = 0;
            $this->transIzgubeHlajenje[$mesec] = 0;

            $this->solarniDobitkiOgrevanje[$mesec] = 0;
            $this->solarniDobitkiHlajenje[$mesec] = 0;

            // notranji dobitki
            $this->notranjiViriOgrevanje[$mesec] = $this->ogrevanaPovrsina *
                $this->notranjiViri->ogrevanje * $stDni * 24 / 1000;
            $this->notranjiViriHlajenje[$mesec] = $this->ogrevanaPovrsina *
                $this->notranjiViri->hlajenje * $stDni * 24 / 1000;
        }

        $this->izracunTransmisijskihIzgub($okolje);
        $this->izracunVentilacijskihIzgub();
        $this->izracunFaktorjaIzkoristka();
        $this->izracunEnergijeOgrevanjeHlajanje();
        $this->izracunTSV();
        $this->izracunRazsvetljave();
        $this->izracunNavlazevanje($okolje);

        // končni izračuni
        $skupni_Uab = 0;
        $this->povrsinaOvoja = 0;
        $this->transparentnaPovrsina = 0;
        foreach ($this->ovoj->netransparentneKonstrukcije as $elementOvoja) {
            $skupni_Uab += $elementOvoja->U * $elementOvoja->povrsina * $elementOvoja->b * $elementOvoja->stevilo;
            $this->povrsinaOvoja += $elementOvoja->povrsina * $elementOvoja->stevilo;
        }
        foreach ($this->ovoj->transparentneKonstrukcije as $elementOvoja) {
            $skupni_Uab += $elementOvoja->U * $elementOvoja->povrsina * $elementOvoja->b * $elementOvoja->stevilo;
            $this->povrsinaOvoja += $elementOvoja->povrsina * $elementOvoja->stevilo;
            //$this->povrsinaOvoja +=
            //    $elementOvoja->povrsina * (1 - $elementOvoja->delezOkvirja) * $elementOvoja->stevilo;
            $this->transparentnaPovrsina +=
                $elementOvoja->povrsina * (1 - $elementOvoja->delezOkvirja) * $elementOvoja->stevilo;
        }

        if ($this->povrsinaOvoja > 0) {
            $this->specTransmisijskeIzgube = $skupni_Uab + $this->povrsinaOvoja * $this->deltaPsi;
            $this->specVentilacijskeIzgube = $this->Hve_ogrevanje;

            $this->specLetnaToplota = $this->skupnaEnergijaOgrevanje / $this->ogrevanaPovrsina;
            $this->specLetniHlad = $this->skupnaEnergijaHlajenje / $this->ogrevanaPovrsina;

            $this->faktorOblike = round($this->povrsinaOvoja / $this->brutoProstornina, 3);

            $this->specKoeficientTransmisijskihIzgub = $skupni_Uab / $this->povrsinaOvoja + $this->deltaPsi;

            $povprecnaLetnaTemp = $okolje->povprecnaLetnaTemp < 7 ? 7 :
                ($okolje->povprecnaLetnaTemp > 11 ? 11 : $okolje->povprecnaLetnaTemp);
            $faktorOblike = $this->faktorOblike < 0.2 ? 0.2 : ($this->faktorOblike > 1.2 ? 1.2 : $this->faktorOblike);

            $this->dovoljenSpecKoeficientTransmisijskihIzgub = 0.25 +
                $povprecnaLetnaTemp / 300 +
                0.04 / $faktorOblike +
                ($this->transparentnaPovrsina / $this->povrsinaOvoja) / 8;
        }

        Log::info(sprintf(
            '"%s": Konec analize cone' . (empty($this->options['referencnaStavba']) ? '' : ' :: REF'),
            $this->id
        ));
    }

    /**
     * Izračun navlaževanje
     *
     * @param \stdClass $okolje Podatki okolj
     * @param array $options Opcije za izračun
     * @return void
     */
    private function izracunTransmisijskihIzgub($okolje, $options = [])
    {
        foreach ($this->ovoj->transparentneKonstrukcije as $elementOvoja) {
            $elementOvoja->analiza($this, $okolje);

            $this->transIzgubeOgrevanje =
                array_sum_values($this->transIzgubeOgrevanje, $elementOvoja->transIzgubeOgrevanje);
            $this->transIzgubeHlajenje =
                array_sum_values($this->transIzgubeHlajenje, $elementOvoja->transIzgubeHlajenje);

            $this->solarniDobitkiOgrevanje =
                array_sum_values($this->solarniDobitkiOgrevanje, $elementOvoja->solarniDobitkiOgrevanje);

                $this->solarniDobitkiHlajenje =
                array_sum_values($this->solarniDobitkiHlajenje, $elementOvoja->solarniDobitkiHlajenje);
        }

        foreach ($this->ovoj->netransparentneKonstrukcije as $elementOvoja) {
            $elementOvoja->analiza($this, $okolje);

            $this->transIzgubeOgrevanje =
                array_sum_values($this->transIzgubeOgrevanje, $elementOvoja->transIzgubeOgrevanje);
            $this->transIzgubeHlajenje =
                array_sum_values($this->transIzgubeHlajenje, $elementOvoja->transIzgubeHlajenje);

            $this->solarniDobitkiOgrevanje =
                array_sum_values($this->solarniDobitkiOgrevanje, $elementOvoja->solarniDobitkiOgrevanje);
            $this->solarniDobitkiHlajenje =
                array_sum_values($this->solarniDobitkiHlajenje, $elementOvoja->solarniDobitkiHlajenje);
        }
    }

    /**
     * Izračun ventilacijskih izgub za cono
     *
     * @return void
     */
    public function izracunVentilacijskihIzgub()
    {
        // poračun ventilacijskih izgub
        $faktorLokacije = $this->infiltracija->lega->koeficientVplivaVetra($this->infiltracija->zavetrovanost);

        if (
            method_exists($this->klasifikacija, 'kolicinaSvezegaZrakaZaPrezracevanje') &&
            !isset($this->prezracevanje->volumenDovedenegaZraka) &&
            !isset($this->prezracevanje->izmenjava)
        ) {
            $volumenZrakaOgrevanje = $this->klasifikacija->kolicinaSvezegaZrakaZaPrezracevanje($this);
            $volumenZrakaHlajenje = $this->klasifikacija->kolicinaSvezegaZrakaZaPrezracevanje($this);
        } else {
            if (isset($this->prezracevanje->volumenDovedenegaZraka)) {
                if (is_float($this->prezracevanje->volumenDovedenegaZraka)) {
                    $volumenZrakaOgrevanje = $this->prezracevanje->volumenDovedenegaZraka;
                    $volumenZrakaHlajenje = $this->prezracevanje->volumenDovedenegaZraka;
                } else {
                    $volumenZrakaOgrevanje = $this->prezracevanje->volumenDovedenegaZraka->ogrevanje;
                    $volumenZrakaHlajenje = $this->prezracevanje->volumenDovedenegaZraka->hlajenje;
                }
            } elseif (isset($this->prezracevanje->izmenjava)) {
                if (is_float($this->prezracevanje->izmenjava)) {
                    $volumenZrakaOgrevanje = $this->netoProstornina * $this->prezracevanje->izmenjava;
                    $volumenZrakaHlajenje = $this->netoProstornina * $this->prezracevanje->izmenjava;
                } else {
                    $volumenZrakaOgrevanje = $this->netoProstornina * $this->prezracevanje->izmenjava->ogrevanje;
                    $volumenZrakaHlajenje = $this->netoProstornina * $this->prezracevanje->izmenjava->hlajenje;
                }
            } else {
                Log::warn(sprintf('Cona "%s" nima določenega prezračevalnega volumna.', $this->id));
            }
        }

        $this->volumenZrakaOgrevanje = $volumenZrakaOgrevanje ?? 0;
        $this->volumenZrakaHlajenje = $volumenZrakaHlajenje ?? 0;

        switch ($this->prezracevanje->vrsta) {
            case 'naravno':
                $this->Hve_ogrevanje = 0.33 * $this->volumenZrakaOgrevanje;
                $this->Hve_hlajenje = 0.33 * $this->volumenZrakaHlajenje;
                break;
            case 'mehansko':
                $Vinf_ogrevanje = $this->netoProstornina * $this->infiltracija->n50 * $faktorLokacije /
                    (1 + $this->infiltracija->zavetrovanost->faktorVetra() / $faktorLokacije *
                    pow($this->volumenZrakaOgrevanje / ($this->netoProstornina * $this->infiltracija->n50), 2));
                $Vinf_hlajenje = $this->netoProstornina * $this->infiltracija->n50 * $faktorLokacije /
                    (1 + $this->infiltracija->zavetrovanost->faktorVetra() / $faktorLokacije *
                    pow($this->volumenZrakaHlajenje / ($this->netoProstornina * $this->infiltracija->n50), 2));

                $this->Hve_ogrevanje =
                    0.33 * ($this->volumenZrakaOgrevanje + $Vinf_ogrevanje);
                $this->Hve_hlajenje =
                    0.33 * ($this->volumenZrakaHlajenje + $Vinf_hlajenje);
                break;
            case 'rekuperacija':
                $Vinf_ogrevanje = $this->netoProstornina * $this->infiltracija->n50 * $faktorLokacije;
                $Vinf_hlajenje = $this->netoProstornina * $this->infiltracija->n50 * $faktorLokacije;
                $this->Hve_ogrevanje = 0.33 * ($Vinf_ogrevanje +
                    (1 - $this->prezracevanje->izkoristek) * $this->volumenZrakaOgrevanje);
                $this->Hve_hlajenje = 0.33 * ($Vinf_hlajenje + $this->volumenZrakaHlajenje);
                break;
            default:
                $this->Hve_ogrevanje = 0;
                $this->Hve_hlajenje = 0;
        }

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);
            $this->prezracevalneIzgubeOgrevanje[$mesec] = $this->Hve_ogrevanje *
                $this->deltaTOgrevanje[$mesec] * $stDni * 24 / 1000;
            $this->prezracevalneIzgubeHlajenje[$mesec] = $this->Hve_hlajenje *
                $this->deltaTHlajenje[$mesec] * $stDni * 24 / 1000;
        }
    }

    /**
     * Izračun faktorja izkoristka za ogrevanje
     *
     * @return void
     */
    public function izracunFaktorjaIzkoristka()
    {
        foreach ($this->ovoj->transparentneKonstrukcije as $elementOvoja) {
            $this->Htr_ogrevanje += $elementOvoja->H_ogrevanje;
            $this->Htr_hlajenje += $elementOvoja->H_hlajenje;
        }
        foreach ($this->ovoj->netransparentneKonstrukcije as $elementOvoja) {
            if ($elementOvoja->protiZraku) {
                $this->Htr_ogrevanje += $elementOvoja->H_ogrevanje;
                $this->Htr_hlajenje += $elementOvoja->H_hlajenje;
            } else {
                $this->Hgr_ogrevanje += $elementOvoja->H_ogrevanje;
                $this->Hgr_hlajenje += $elementOvoja->H_hlajenje;
            }
        }

        $Cm_eff = $this->ogrevanaPovrsina * $this->toplotnaKapaciteta;

        $tau_ogrevanje = $Cm_eff / 3600 / ($this->Htr_ogrevanje + $this->Hgr_ogrevanje + $this->Hve_ogrevanje);
        $A_ogrevanje = 1 + $tau_ogrevanje / 15;

        $tau_hlajenje = $Cm_eff / 3600 / ($this->Htr_hlajenje + $this->Hgr_hlajenje + $this->Hve_hlajenje);
        $A_hlajenje = 1 + $tau_hlajenje / 15;

        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $vsotaVirov_ogrevanje =
                $this->notranjiViriOgrevanje[$mesec] +
                $this->solarniDobitkiOgrevanje[$mesec] +
                ($this->vrnjeneIzgubeVOgrevanje[$mesec] ?? 0);
            $vsotaPonorov_ogrevanje =
                (float)($this->prezracevalneIzgubeOgrevanje[$mesec] + $this->transIzgubeOgrevanje[$mesec]);

            if ($vsotaPonorov_ogrevanje == 0.0) {
                $gama_ogrevanje = -1;
            } else {
                $gama_ogrevanje = $vsotaVirov_ogrevanje / $vsotaPonorov_ogrevanje;
            }

            $this->ucinekDobitkov[$mesec] = null;
            if ($gama_ogrevanje > -0.1 && $gama_ogrevanje < 2) {
                if ($gama_ogrevanje < 0) {
                    if ((float)$vsotaVirov_ogrevanje > 0) {
                        $this->ucinekDobitkov[$mesec] = 1;
                    } else {
                        $this->ucinekDobitkov[$mesec] = 1 / $gama_ogrevanje;
                    }
                } else {
                    if ($gama_ogrevanje == 1) {
                        $this->ucinekDobitkov[$mesec] = $A_ogrevanje / ($A_ogrevanje + 1);
                    } else {
                        $this->ucinekDobitkov[$mesec] =
                            (1 - pow($gama_ogrevanje, $A_ogrevanje)) / (1 - pow($gama_ogrevanje, $A_ogrevanje + 1));
                    }
                }
            }

            $vsotaVirov_hlajenje = $this->notranjiViriHlajenje[$mesec] + $this->solarniDobitkiHlajenje[$mesec];
            $vsotaPonorov_hlajenje =
                (float)$this->prezracevalneIzgubeHlajenje[$mesec] + $this->transIzgubeHlajenje[$mesec];

            if ($vsotaPonorov_hlajenje == 0.0) {
                $gama_hlajenje = -1;
            } else {
                $gama_hlajenje = $vsotaVirov_hlajenje / $vsotaPonorov_hlajenje;
            }

            $this->ucinekPonorov[$mesec] = null;
            if (1 / $gama_hlajenje <= 2) {
                if ($gama_hlajenje <= 0) {
                    $this->ucinekPonorov[$mesec] = 1;
                } else {
                    if ($gama_hlajenje == 1) {
                        $this->ucinekPonorov[$mesec] = $A_hlajenje / ($A_hlajenje + 1);
                    } else {
                        $this->ucinekPonorov[$mesec] =
                            (1 - pow($gama_hlajenje, -$A_hlajenje)) / (1 - pow($gama_hlajenje, -$A_hlajenje - 1));
                    }
                }
            }
        }
    }

    /**
     * Izračun potrebne energije za ogrevanje in hlajenje
     *
     * @return void
     */
    public function izracunEnergijeOgrevanjeHlajanje()
    {
        $this->skupnaEnergijaOgrevanje = 0;
        $this->skupnaEnergijaHlajenje = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $this->energijaOgrevanje[$mesec] = 0;
            if ($this->ucinekDobitkov[$mesec]) {
                $this->energijaOgrevanje[$mesec] =
                    $this->transIzgubeOgrevanje[$mesec] + $this->prezracevalneIzgubeOgrevanje[$mesec] -
                    $this->ucinekDobitkov[$mesec] * ($this->notranjiViriOgrevanje[$mesec] +
                    $this->solarniDobitkiOgrevanje[$mesec] + ($this->vrnjeneIzgubeVOgrevanje[$mesec] ?? 0));

                if ($this->energijaOgrevanje[$mesec] < 0) {
                    $this->energijaOgrevanje[$mesec] = 0;
                }
            }
            $this->skupnaEnergijaOgrevanje += $this->energijaOgrevanje[$mesec];

            $this->energijaHlajenje[$mesec] = 0;
            if ($this->ucinekPonorov[$mesec]) {
                $this->energijaHlajenje[$mesec] =
                    $this->notranjiViriHlajenje[$mesec] + $this->solarniDobitkiHlajenje[$mesec] -
                    $this->ucinekPonorov[$mesec] * ($this->transIzgubeHlajenje[$mesec] +
                    $this->prezracevalneIzgubeHlajenje[$mesec]);
            }
            $this->skupnaEnergijaHlajenje += $this->energijaHlajenje[$mesec];
        }
    }

    /**
     * Izračun energije za TSV
     *
     * @return void
     */
    public function izracunTSV()
    {
        if (!is_null($this->TSV)) {
            $this->skupnaEnergijaTSV = 0;
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->energijaTSV[$mesec] = $this->klasifikacija->izracunTSVZaMesec($mesec, $this);
                $this->skupnaEnergijaTSV += $this->energijaTSV[$mesec];
            }
        } else {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->energijaTSV[$mesec] = 0;
            }
        }
    }

    /**
     * Izračun električne energije za razsvetljavo
     *
     * @return void
     */
    public function izracunRazsvetljave()
    {
        $TSSRazsvetljava = new Razsvetljava($this->razsvetljava);
        $TSSRazsvetljava->analiza([], $this, null, $this->options);
        $this->energijaRazsvetljava = $TSSRazsvetljava->potrebnaEnergija;
        $this->skupnaEnergijaRazsvetljava = $TSSRazsvetljava->skupnaPotrebnaEnergija;
    }

    /**
     * Izračun navlaževanje
     *
     * @param \stdClass $okolje Podatki okolj
     * @param array $options Opcije za izračun
     * @return void
     */
    public function izracunNavlazevanje($okolje, $options = [])
    {
        if (!isset($this->uravnavanjeVlage)) {
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $this->energijaNavlazevanje[$mesec] = 0;
                $this->energijaRazvlazevanje[$mesec] = 0;
            }
            $this->skupnaEnergijaNavlazevanje = 0;
            $this->skupnaEnergijaRazvlazevanje = 0;
        } else {
            $uparjalnaToplota = 2466;

            $this->uravnavanjeVlage->vlaznostZrakaNavlazevanje =
                $this->uravnavanjeVlage->vlaznostZrakaNavlazevanje ?? 30;
            $this->uravnavanjeVlage->vlaznostZrakaRazvlazevanje =
                $this->uravnavanjeVlage->vlaznostZrakaRazvlazevanje ?? 60;

            // tabela 7.1.1
            $this->uravnavanjeVlage->faktorUporabe = $this->uravnavanjeVlage->faktorUporabe ?? 1;

            // specifična količina oddane vodne pare virov v stavbi na m²
            // g_h2o,h (kg/m²h)
            $this->uravnavanjeVlage->viriVodnePare = $this->uravnavanjeVlage->viriVodnePare ?? 1.4;

            // samo za navlaževanje
            $this->uravnavanjeVlage->ucinkovitostPrenosnika = $this->uravnavanjeVlage->ucinkovitostPrenosnika ?? 0.55;

            $nasicenNotranjiTlakOgrevanje =
                611.2 * exp(17.62 * $this->notranjaTOgrevanje / (243.12 + $this->notranjaTOgrevanje));
            $nasicenNotranjiTlakHlajenje =
                611.2 * exp(17.62 * $this->notranjaTHlajenje / (243.12 + $this->notranjaTHlajenje));

            // x_i,a,min,m
            $minNotranjaVlaznostOgrevanje = $this->uravnavanjeVlage->minNotranjaVlaznostOgrevanje ??
                0.622 * 1000 * $this->uravnavanjeVlage->vlaznostZrakaNavlazevanje / 100 *
                $nasicenNotranjiTlakOgrevanje /
                (101325 - $this->uravnavanjeVlage->vlaznostZrakaNavlazevanje / 100 * $nasicenNotranjiTlakOgrevanje);
            $minNotranjaVlaznostHlajenje = $this->uravnavanjeVlage->minNotranjaVlaznostHlajenje ??
                0.622 * 1000 * $this->uravnavanjeVlage->vlaznostZrakaNavlazevanje / 100 *
                $nasicenNotranjiTlakHlajenje /
                (101325 - $this->uravnavanjeVlage->vlaznostZrakaNavlazevanje / 100 * $nasicenNotranjiTlakHlajenje);

            $this->skupnaEnergijaNavlazevanje = 0;
            $this->skupnaEnergijaRazvlazevanje = 0;
            foreach (array_keys(Calc::MESECI) as $mesec) {
                $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

                $nasicenZunanjiTlak =
                    611.2 * exp(17.62 * $okolje->zunanjaT[$mesec] / (243.12 + $okolje->zunanjaT[$mesec]));

                // X_e,a,m
                $absZunanjaVlaznost = $okolje->absVlaznost[$mesec] ??
                    (0.622 * 1000 * $okolje->zunanjaVlaga[$mesec] / 100 *
                    $nasicenZunanjiTlak /
                    (101325 - $okolje->zunanjaVlaga[$mesec] / 100 * $nasicenZunanjiTlak));

                // m_h2o,HU,m
                $potrebnaMesecnaKolicinaVodeOgrevanje =
                    0.001 * 1.25 * $this->prezracevanje->volumenDovedenegaZraka->ogrevanje *
                    ($minNotranjaVlaznostOgrevanje - $absZunanjaVlaznost) * 24 * $stDni;
                $potrebnaMesecnaKolicinaVodeHlajenje =
                    0.001 * 1.25 * $this->prezracevanje->volumenDovedenegaZraka->hlajenje *
                    ($absZunanjaVlaznost - $minNotranjaVlaznostHlajenje) * 24 * $stDni;

                // G_h2o,m
                $mesecnaKolicinaVodnePare = $this->uravnavanjeVlage->faktorUporabe *
                    $this->uravnavanjeVlage->viriVodnePare * $this->ogrevanaPovrsina * 24 * $stDni / 1000;

                $this->energijaNavlazevanje[$mesec] =
                    ($potrebnaMesecnaKolicinaVodeOgrevanje - $mesecnaKolicinaVodnePare) *
                    (1 - $this->uravnavanjeVlage->ucinkovitostPrenosnika) * $uparjalnaToplota / 3600;
                $this->energijaRazvlazevanje[$mesec] =
                    ($potrebnaMesecnaKolicinaVodeHlajenje + $mesecnaKolicinaVodnePare) * $uparjalnaToplota / 3600;

                if (!empty($options['details'])) {
                    // za validacijo
                    $this->uravnavanjeVlage->absZunanjaVlaznost[$mesec] = $absZunanjaVlaznost;
                    $this->uravnavanjeVlage->potrebnaMesecnaKolicinaVodeOgrevanje[$mesec] =
                        $potrebnaMesecnaKolicinaVodeOgrevanje;
                    $this->uravnavanjeVlage->potrebnaMesecnaKolicinaVodeHlajenje[$mesec] =
                        $potrebnaMesecnaKolicinaVodeHlajenje;
                    $this->uravnavanjeVlage->mesecnaKolicinaVodnePare[$mesec] = $mesecnaKolicinaVodnePare;
                }

                $this->skupnaEnergijaNavlazevanje += $this->energijaNavlazevanje[$mesec];
                $this->skupnaEnergijaRazvlazevanje += $this->energijaRazvlazevanje[$mesec];
            }
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $cona = new \stdClass();

        $reflect = new \ReflectionClass(self::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->getName() == 'klasifikacija') {
                $cona->klasifikacija = $this->klasifikacija->export();
            } elseif ($prop->getName() == 'ovoj') {
                $cona->ovoj = new \stdClass();
                $cona->ovoj->netransparentneKonstrukcije = [];
                $cona->ovoj->transparentneKonstrukcije = [];
                if (!empty($this->ovoj->netransparentneKonstrukcije)) {
                    foreach ($this->ovoj->netransparentneKonstrukcije as $elementOvoja) {
                        $cona->ovoj->netransparentneKonstrukcije[] = $elementOvoja->export();
                    }
                }
                if (!empty($this->ovoj->transparentneKonstrukcije)) {
                    foreach ($this->ovoj->transparentneKonstrukcije as $elementOvoja) {
                        $cona->ovoj->transparentneKonstrukcije[] = $elementOvoja->export();
                    }
                }
            } elseif ($prop->getName() == 'infiltracija') {
                $cona->infiltracija = $prop->getValue($this);
                $cona->infiltracija->lega = $this->infiltracija->lega->value;
                $cona->infiltracija->zavetrovanost = $this->infiltracija->zavetrovanost->value;
            } elseif ($prop->getName() == 'uravnavanjeVlage') {
                //TODO:
            } else {
                if ($prop->isInitialized($this)) {
                    $cona->{$prop->getName()} = $prop->getValue($this);
                }
            }
        }

        return $cona;
    }

    /**
     * Vrnje referenčni tss za predmetno cono, glede na vrsto $TSS
     *
     * @param string $TSS Za kateri sistem gre ('oht', 'prezracevanje', 'razsvetljava', 'fotovoltaika')
     * @return array
     */
    public function referencniTSS(string $TSS): array
    {
        switch ($TSS) {
            case 'prezracevanje':
                $ret = $this->klasifikacija->referencniTSSPrezracevanja($this);
                break;
            case 'razsvetljava':
                $ret = $this->klasifikacija->referencniTSSRazsvetljava($this);
                break;
            case 'OHT':
                $ret = $this->klasifikacija->referencniTSSOHT($this);
                break;
            default:
                throw new \Exception(sprintf('Neznan TSS "%s"', $TSS));
        }

        return $ret;
    }
}
