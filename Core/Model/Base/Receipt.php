<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Model\FormaPago;

/**
 * Description of Receipt
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class Receipt extends ModelOnChangeClass
{

    /**
     *
     * @var string
     */
    public $coddivisa;

    /**
     *
     * @var string
     */
    public $codpago;

    /**
     *
     * @var string
     */
    public $fecha;

    /**
     *
     * @var string
     */
    public $fechapago;

    /**
     *
     * @var int
     */
    public $idempresa;

    /**
     *
     * @var int
     */
    public $idfactura;

    /**
     *
     * @var int
     */
    public $idrecibo;

    /**
     *
     * @var float
     */
    public $importe;

    /**
     *
     * @var float
     */
    public $liquidado;

    /**
     *
     * @var string
     */
    public $nick;

    /**
     *
     * @var string
     */
    public $observaciones;

    /**
     *
     * @var bool
     */
    public $pagado;

    /**
     *
     * @var string
     */
    public $vencimiento;

    abstract public function newPayment();

    public function clear()
    {
        parent::clear();
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
        $this->codpago = AppSettings::get('default', 'codpago');
        $this->fecha = date('d-m-Y');
        $this->importe = 0.0;
        $this->liquidado = 0.0;
        $this->pagado = false;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idrecibo';
    }

    /**
     * 
     * @param string $codpago
     */
    public function setPaymentMethod($codpago)
    {
        $formaPago = new FormaPago();
        if ($formaPago->loadFromCode($codpago)) {
            $this->codpago = $codpago;
            $this->vencimiento = $formaPago->getExpiration($this->fecha);
        }
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->observaciones = Utils::noHtml($this->observaciones);

        /// check expiration date
        if (strtotime($this->vencimiento) < strtotime($this->fecha)) {
            return false;
        }

        return parent::test();
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'pagado':
                $this->newPayment();
                return true;

            default:
                return parent::onChange($field);
        }
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = array())
    {
        if (parent::saveInsert($values)) {
            if ($this->pagado) {
                $this->newPayment();
            }

            return true;
        }

        return false;
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        parent::setPreviousData(array_merge(['pagado'], $fields));
    }
}
