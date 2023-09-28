<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone;

use App\Calc\GF\Cone\ElementiOvoja\NetransparentenElementOvoja;
use App\Calc\GF\Cone\ElementiOvoja\TransparentenElementOvoja;
use App\Calc\TSS\Razsvetljava\Razsvetljava;
use App\Core\Log;
use App\Lib\Calc;
use App\Lib\EvalMath;

class Cona
{
    public string $id;
    public string $naziv;
    public string $klasifikacija;
    public float $brutoProstornina = 0;
    public float $netoProstornina = 0;
    public float $ogrevanaPovrsina = 0;
    public float $dolzina = 0;
    public float $sirina = 0;
    public float $etaznaVisina = 0;
    public int $steviloEtaz = 0;

    public float $deltaPsi = 0;

    public float $notranjaTOgrevanje;
    public float $notranjaTHlajenje;

    public float $toplotnaKapaciteta = 0;

    public float $povrsinaOvoja = 0;
    public float $transparentnaPovrsina = 0;
    public float $faktorOblike = 0;

    public \stdClass $infiltracija;
    public \stdClass $notranjiViri;
    public \stdClass $TSV;
    public \stdClass $razsvetljava;
    public \stdClass $prezracevanje;
    public \stdClass $ovoj;

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

    public array $vracljiveIzgube = [];

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
     * @return void
     */
    public function __construct($konstrukcije = null, $config = null)
    {
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

        $EvalMath = EvalMath::getInstance(['decimalSeparator' => '.', 'thousandsSeparator' => '']);

        $reflect = new \ReflectionClass(Cona::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            switch ($prop->getName()) {
                case 'ovoj':
                    $this->ovoj = new \stdClass();
                    $this->ovoj->netransparentneKonstrukcije = [];
                    $this->ovoj->transparentneKonstrukcije = [];

                    if (!empty($config->ovoj->netransparentneKonstrukcije)) {
                        foreach ($config->ovoj->netransparentneKonstrukcije as $konsConfig) {
                            $kons = array_first(
                                $this->konstrukcije->netransparentne,
                                fn($k) => $k->id == $konsConfig->idKonstrukcije
                            );
                            $this->ovoj->netransparentneKonstrukcije[] =
                                new NetransparentenElementOvoja($kons, $konsConfig);
                        }
                    }
                    if (!empty($config->ovoj->transparentneKonstrukcije)) {
                        foreach ($config->ovoj->transparentneKonstrukcije as $konsConfig) {
                            $kons = array_first(
                                $this->konstrukcije->transparentne,
                                fn($k) => $k->id == $konsConfig->idKonstrukcije
                            );
                            $this->ovoj->transparentneKonstrukcije[] =
                                new TransparentenElementOvoja($kons, $konsConfig);
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
        Log::info(sprintf('"%s": Začetek analize cone', $this->id));

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

        Log::info(sprintf('"%s": Konec analize cone', $this->id));
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

        foreach ($this->solarniDobitkiOgrevanje as $k => $mesec) {
            if ($this->solarniDobitkiOgrevanje[$k] < 0) {
                $this->solarniDobitkiOgrevanje[$k] = 0;
            }
        }
        foreach ($this->solarniDobitkiHlajenje as $k => $mesec) {
            if ($this->solarniDobitkiHlajenje[$k] < 0) {
                $this->solarniDobitkiHlajenje[$k] = 0;
            }
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
        $faktorVetra = $this->infiltracija->zavetrovanost == 1 ? 15 : 20;

        $faktorLokacijeLookup = [
            0 => [0.1, 0.07, 0.04],
            1 => [0.03, 0.02, 0.01],
        ];
        $faktorLokacije = $faktorLokacijeLookup[$this->infiltracija->zavetrovanost - 1][$this->infiltracija->lega - 1];

        $volumenZrakaOgrevanje = $this->prezracevanje->volumenDovedenegaZraka->ogrevanje ??
            $this->netoProstornina * $this->prezracevanje->izmenjava->ogrevanje;
        $volumenZrakaHlajenje = $this->prezracevanje->volumenDovedenegaZraka->hlajenje ??
            $this->netoProstornina * $this->prezracevanje->izmenjava->hlajenje;

        switch ($this->prezracevanje->vrsta) {
            case 'naravno':
                $this->Hve_ogrevanje = 0.33 * $volumenZrakaOgrevanje;
                $this->Hve_hlajenje = 0.33 * $volumenZrakaHlajenje;
                break;
            case 'mehansko':
                $Vinf_ogrevanje = $this->netoProstornina * $this->infiltracija->n50 * $faktorLokacije /
                    (1 + $faktorVetra / $faktorLokacije *
                    pow($volumenZrakaOgrevanje / ($this->netoProstornina * $this->infiltracija->n50), 2));
                $Vinf_hlajenje = $this->netoProstornina * $this->infiltracija->n50 * $faktorLokacije /
                    (1 + $faktorVetra / $faktorLokacije *
                    pow($volumenZrakaHlajenje / ($this->netoProstornina * $this->infiltracija->n50), 2));

                $this->Hve_ogrevanje =
                    0.33 * ($volumenZrakaOgrevanje + $Vinf_ogrevanje);
                $this->Hve_hlajenje =
                    0.33 * ($volumenZrakaHlajenje + $Vinf_hlajenje);
                break;
            case 'rekuperacija':
                $Vinf_ogrevanje = $this->netoProstornina * $this->infiltracija->n50 * $faktorLokacije;
                $Vinf_hlajenje = $this->netoProstornina * $this->infiltracija->n50 * $faktorLokacije;
                $this->Hve_ogrevanje = 0.33 * ($Vinf_ogrevanje +
                    (1 - $this->prezracevanje->izkoristek) * $volumenZrakaOgrevanje);
                $this->Hve_hlajenje = 0.33 * ($Vinf_hlajenje + $volumenZrakaHlajenje);
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
     * Izračun faktorjev Htr, Hgr,
     *
     * @return void
     */
    public function izracunFaktorjaH()
    {
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
                ($this->vracljiveIzgube[$mesec] ?? 0);
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
                    $this->solarniDobitkiOgrevanje[$mesec] + ($this->vracljiveIzgube[$mesec] ?? 0));

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
        if (empty($this->TSV->steviloOseb)) {
            switch ($this->klasifikacija) {
                case 'St-1':
                    $this->TSV->steviloOseb = 0.025 * $this->ogrevanaPovrsina;
                    if ($this->TSV->steviloOseb > 1.75) {
                        $this->TSV->steviloOseb = 1.75 + 0.3 * ($this->TSV->steviloOseb - 1.75);
                    }
                    break;
                case 'St-2':
                case 'St-3':
                    if ($this->ogrevanaPovrsina > 50) {
                        $this->TSV->steviloOseb = 0.035 * $this->ogrevanaPovrsina;
                        if ($this->TSV->steviloOseb > 1.75) {
                            $this->TSV->steviloOseb = 1.75 + 0.3 * (0.035 * $this->ogrevanaPovrsina - 1.75);
                        }
                    } else {
                        $this->TSV->steviloOseb = 1.75 - 0.01875 * (50 - $this->ogrevanaPovrsina);
                        if ($this->TSV->steviloOseb > 1.75) {
                            $this->TSV->steviloOseb = 1.75 + 0.3 * (0.035 * $this->ogrevanaPovrsina - 1.75);
                        }
                    }
                    break;
                default:
                    throw new \Exception('TSV: Klasifikacija cone je neveljavna');
            }
        }
        if (empty($this->TSV->dnevnaKolicina)) {
            $this->TSV->dnevnaKolicina = min(40.71, 3.26 * $this->ogrevanaPovrsina / $this->TSV->steviloOseb);
        }

        $this->TSV->toplaVodaT = $this->TSV->toplaVodaT ?? 42;
        $this->TSV->hladnaVodaT = $this->TSV->hladnaVodaT ?? 10;

        $this->skupnaEnergijaTSV = 0;
        foreach (array_keys(Calc::MESECI) as $mesec) {
            $stDni = cal_days_in_month(CAL_GREGORIAN, $mesec + 1, 2023);

            $this->energijaTSV[$mesec] = 0.001 * $this->TSV->dnevnaKolicina * $this->TSV->steviloOseb * 4.2 / 3.6 *
                ($this->TSV->toplaVodaT - $this->TSV->hladnaVodaT) * $stDni;

            $this->skupnaEnergijaTSV += $this->energijaTSV[$mesec];
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
        $TSSRazsvetljava->analiza([], $this, null);
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
        if (empty($this->uravnavanjeVlage)) {
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

            // specifična količina oddane vodne pare virov v stavbi na m2
            // g_h2o,h (kg/m2h)
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

        $reflect = new \ReflectionClass(Cona::class);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            if ($prop->getName() == 'ovoj') {
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
            } else {
                $cona->{$prop->getName()} = $prop->getValue($this);
            }
        }

        return $cona;
    }
}
