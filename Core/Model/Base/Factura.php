<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Core\Model\Base;

/**
 * Description of Factura
 *
 * @author carlos
 */
trait Factura
{

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idfactura;

    /**
     * ID del asiento relacionado, si lo hay.
     *
     * @var int
     */
    public $idasiento;

    /**
     * ID del asiento de pago relacionado, si lo hay.
     *
     * @var int
     */
    public $idasientop;

    /**
     * ID de la factura que rectifica.
     *
     * @var int
     */
    public $idfacturarect;

    /**
     * Código de la factura que rectifica.
     *
     * @var string
     */
    public $codigorect;

    /**
     * TRUE => pagada
     *
     * @var bool
     */
    public $pagada;

    /**
     * TRUE => anulada
     *
     * @var bool
     */
    public $anulada;

    /**
     * Fecha de vencimiento de la factura.
     *
     * @var string
     */
    public $vencimiento;

    /**
     * Devuelve el asiento asociado
     *
     * @return bool|Asiento
     */
    public function getAsiento()
    {
        $asiento = new Asiento();

        return $asiento->get($this->idasiento);
    }

    /**
     * Devuelve el asiento de pago asociado
     *
     * @return bool|mixed
     */
    public function getAsientoPago()
    {
        $asiento = new Asiento();

        return $asiento->get($this->idasientop);
    }

    public function getLineasIvaTrait($lineas, $lineaIvaClass = 'LineaIvaFacturaCliente')
    {
        $lineaIva = new $lineaIvaClass();
        $lineasi = $lineaIva->allFromFactura($this->idfactura);
        /// si no hay lineas de IVA las generamos
        if (!empty($lineasi)) {
            if (!empty($lineas)) {
                foreach ($lineas as $l) {
                    $i = 0;
                    $encontrada = false;
                    while ($i < count($lineasi)) {
                        if ($l->iva === $lineasi[$i]->iva && $l->recargo === $lineasi[$i]->recargo) {
                            $encontrada = true;
                            $lineasi[$i]->neto += $l->pvptotal;
                            $lineasi[$i]->totaliva += ($l->pvptotal * $l->iva) / 100;
                            $lineasi[$i]->totalrecargo += ($l->pvptotal * $l->recargo) / 100;
                        }
                        ++$i;
                    }
                    if (!$encontrada) {
                        $lineasi[$i] = new $lineaIvaClass();
                        $lineasi[$i]->idfactura = $this->idfactura;
                        $lineasi[$i]->codimpuesto = $l->codimpuesto;
                        $lineasi[$i]->iva = $l->iva;
                        $lineasi[$i]->recargo = $l->recargo;
                        $lineasi[$i]->neto = $l->pvptotal;
                        $lineasi[$i]->totaliva = ($l->pvptotal * $l->iva) / 100;
                        $lineasi[$i]->totalrecargo = ($l->pvptotal * $l->recargo) / 100;
                    }
                }

                /// redondeamos y guardamos
                if (count($lineasi) === 1) {
                    $lineasi[0]->neto = round($lineasi[0]->neto, FS_NF0);
                    $lineasi[0]->totaliva = round($lineasi[0]->totaliva, FS_NF0);
                    $lineasi[0]->totaliva = round($lineasi[0]->totaliva, FS_NF0);
                    $lineasi[0]->totallinea = $lineasi[0]->neto + $lineasi[0]->totaliva + $lineasi[0]->totalrecargo;
                    $lineasi[0]->save();
                } else {
                    /*
                     * Como el neto y el iva se redondean en la factura, al dividirlo
                     * en líneas de iva podemos encontrarnos con un descuadre que
                     * hay que calcular y solucionar.
                     */
                    $tNeto = 0;
                    $tIva = 0;
                    foreach ($lineasi as $li) {
                        $li->neto = bround($li->neto, FS_NF0);
                        $li->totaliva = bround($li->totaliva, FS_NF0);
                        $li->totallinea = $li->neto + $li->totaliva;

                        $tNeto += $li->neto;
                        $tIva += $li->totaliva;
                    }

                    if (!$this->floatcmp($this->neto, $tNeto)) {
                        /*
                         * Sumamos o restamos un céntimo a los netos más altos
                         * hasta que desaparezca el descuadre
                         */
                        $diferencia = round(($this->neto - $tNeto) * 100);
                        usort(
                            $lineasi, function($a, $b) {
                            if ($a->totallinea === $b->totallinea) {
                                return 0;
                            }
                            if ($a->totallinea < 0) {
                                return ($a->totallinea < $b->totallinea) ? -1 : 1;
                            }

                            return ($a->totallinea < $b->totallinea) ? 1 : -1;
                        }
                        );

                        foreach ($lineasi as $i => $value) {
                            if ($diferencia > 0) {
                                $lineasi[$i]->neto += .01;
                                --$diferencia;
                            } elseif ($diferencia < 0) {
                                $lineasi[$i]->neto -= .01;
                                ++$diferencia;
                            } else {
                                break;
                            }
                        }
                    }

                    if (!$this->floatcmp($this->totaliva, $tIva)) {
                        /*
                         * Sumamos o restamos un céntimo a los importes más altos
                         * hasta que desaparezca el descuadre
                         */
                        $diferencia = round(($this->totaliva - $tIva) * 100);
                        usort(
                            $lineasi, function($a, $b) {
                            if ($a->totaliva === $b->totaliva) {
                                return 0;
                            }
                            if ($a->totallinea < 0) {
                                return ($a->totaliva < $b->totaliva) ? -1 : 1;
                            }

                            return ($a->totaliva < $b->totaliva) ? 1 : -1;
                        }
                        );

                        foreach ($lineasi as $i => $value) {
                            if ($diferencia > 0) {
                                $lineasi[$i]->totaliva += .01;
                                --$diferencia;
                            } elseif ($diferencia < 0) {
                                $lineasi[$i]->totaliva -= .01;
                                ++$diferencia;
                            } else {
                                break;
                            }
                        }
                    }

                    foreach ($lineasi as $i => $value) {
                        $lineasi[$i]->totallinea = $value->neto + $value->totaliva + $value->totalrecargo;
                        $lineasi[$i]->save();
                    }
                }
            }
        }

        return $lineasi;
    }
}
