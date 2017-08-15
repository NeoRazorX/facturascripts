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
     * @var int
     */
    public $idfactura;

    /**
     * ID del asiento relacionado, si lo hay.
     * @var int
     */
    public $idasiento;

    /**
     * ID del asiento de pago relacionado, si lo hay.
     * @var int
     */
    public $idasientop;

    /**
     * ID de la factura que rectifica.
     * @var int
     */
    public $idfacturarect;

    /**
     * Código de la factura que rectifica.
     * @var string
     */
    public $codigorect;

    /**
     * TRUE => pagada
     * @var bool
     */
    public $pagada;

    /**
     * TRUE => anulada
     * @var bool
     */
    public $anulada;

    /**
     * Fecha de vencimiento de la factura.
     * @var string
     */
    public $vencimiento;

    /**
     * Devuelve la url donde ver/modificar estos datos del asiento
     * @return string
     */
    public function asientoUrl()
    {
        if ($this->idasiento === null) {
            return 'index.php?page=ContabilidadAsientos';
        }
        return 'index.php?page=ContabilidadAsiento&id=' . $this->idasiento;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos del asiento de pago
     * @return string
     */
    public function asientoPagoUrl()
    {
        if ($this->idasientop === null) {
            return 'index.php?page=ContabilidadAsientos';
        }
        return 'index.php?page=ContabilidadAsiento&id=' . $this->idasientop;
    }

    /**
     * Devuelve el asiento asociado
     * @return bool|Asiento
     */
    public function getAsiento()
    {
        $asiento = new Asiento();
        return $asiento->get($this->idasiento);
    }

    /**
     * Devuelve el asiento de pago asociado
     * @return bool|mixed
     */
    public function getAsientoPago()
    {
        $asiento = new Asiento();
        return $asiento->get($this->idasientop);
    }
}
