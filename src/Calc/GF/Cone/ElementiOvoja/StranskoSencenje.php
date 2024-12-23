<?php
declare(strict_types=1);

namespace App\Calc\GF\Cone\ElementiOvoja;

class StranskoSencenje
{
    public float $zgorajDolzina = 0;
    public float $zgorajRazdalja = 0;
    public float $levoDolzina = 0;
    public float $levoRazdalja = 0;
    public float $desnoDolzina = 0;
    public float $desnoRazdalja = 0;

    /**
     * Merge to existing
     *
     * @param \App\Calc\GF\Cone\ElementiOvoja\StranskoSencenje|\stdClass $source Source Class
     * @return void
     */
    public function merge(StranskoSencenje | \stdClass $source)
    {
        if (!empty($source->zgorajDolzina)) {
            $this->zgorajDolzina = $source->zgorajDolzina;
        }
        if (!empty($source->zgorajRazdalja)) {
            $this->zgorajRazdalja = $source->zgorajRazdalja;
        }
        if (!empty($source->levoDolzina)) {
            $this->levoDolzina = $source->levoDolzina;
        }
        if (!empty($source->levoRazdalja)) {
            $this->levoRazdalja = $source->levoRazdalja;
        }
        if (!empty($source->desnoDolzina)) {
            $this->desnoDolzina = $source->desnoDolzina;
        }
        if (!empty($source->desnoRazdalja)) {
            $this->desnoRazdalja = $source->desnoRazdalja;
        }
    }

    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $stranskoSencenje = new \stdClass();
        $stranskoSencenje->zgorajDolzina = $this->zgorajDolzina;
        $stranskoSencenje->zgorajRazdalja = $this->zgorajRazdalja;
        $stranskoSencenje->levoDolzina = $this->levoDolzina;
        $stranskoSencenje->levoRazdalja = $this->levoRazdalja;
        $stranskoSencenje->desnoDolzina = $this->desnoDolzina;
        $stranskoSencenje->desnoRazdalja = $this->desnoRazdalja;

        return $stranskoSencenje;
    }
}
