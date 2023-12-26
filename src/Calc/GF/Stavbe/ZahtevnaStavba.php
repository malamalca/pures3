<?php
declare(strict_types=1);

namespace App\Calc\GF\Stavbe;

class ZahtevnaStavba extends ManjzahtevnaStavba
{
    /**
     * Export v json
     *
     * @return \stdClass
     */
    public function export()
    {
        $stavba = parent::export();

        $stavba->H_nd_dov = $this->H_nd_dov();
        $stavba->C_nd_dov = $this->C_nd_dov();

        return $stavba;
    }

    /**
     * tabela 7: Dovoljen razmernik potrebne toplote za ogrevanje in potrebne odvedene toplote za hlajenje energetsko zahtevne stavbe
     *
     * @return float
     */
    // phpcs:ignore
    public function H_nd_dov()
    {
        $ret = 0.9;
        if ($this->javna) {
            $ret = 0.8;
        }

        return $ret;
    }

    /**
     * tabela 7: Dovoljen razmernik potrebne toplote za ogrevanje in potrebne odvedene toplote za hlajenje energetsko zahtevne stavbe
     *
     * @return float
     */
    // phpcs:ignore
    public function C_nd_dov()
    {
        $ret = 0.9;
        if ($this->javna) {
            $ret = 0.8;
        }

        return $ret;
    }
}
