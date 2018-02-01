<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;

/**
 * Payment method of an invoice, delivery note, order or estimation.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FormaPago extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key. Varchar (10).
     *
     * @var string
     */
    public $codpago;

    /**
     * Description of the payment method.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Paid -> mark the invoices generated as paid.
     *
     * @var string
     */
    public $genrecibos;

    /**
     * Code of the associated bank account.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * To indicate if it is necessary to show the bank account of the client.
     *
     * @var bool
     */
    public $domiciliado;

    /**
     * True (default) -> display the data in sales documents,
     * including the associated bank account.
     *
     * @var bool
     */
    public $imprimir;

    /**
     * It serves to generate the due date of the invoices.
     *
     * @var string
     */
    public $vencimiento;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'formaspago';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codpago';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->descripcion = '';
        $this->genrecibos = 'Emitidos';
        $this->codcuenta = '';
        $this->domiciliado = false;
        $this->imprimir = true;
        $this->vencimiento = '+1day';
    }

    /**
     * Returns True if is the default payment method for the company.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codpago === AppSettings::get('default', 'codpago');
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = Utils::noHtml($this->descripcion);

        /// we check the expiration validity
        $fecha1 = date('d-m-Y');
        $fecha2 = date('d-m-Y', strtotime($this->vencimiento));
        if (strtotime($fecha1) > strtotime($fecha2)) {
            self::$miniLog->alert(self::$i18n->trans('expiration-invalid'));

            return false;
        }

        return true;
    }

    /**
     * From a date returns the new due date based on this form of payment.
     * If $diasDePago is provided, they will be used for the new date.
     *
     * @param string $fechaInicio
     * @param string $diasDePago  dias de pago específicos para el cliente (separados por comas).
     *
     * @return string
     */
    public function calcularVencimiento($fechaInicio, $diasDePago = '')
    {
        $fecha = $this->calcularVencimiento2($fechaInicio);

        /// we validate the days of payment
        $arrayDias = [];
        foreach (str_getcsv($diasDePago) as $d) {
            if ((int) $d >= 1 && (int) $d <= 31) {
                $arrayDias[] = (int) $d;
            }
        }

        if ($arrayDias !== null) {
            foreach ($arrayDias as $i => $diaDePago) {
                if ($i === 0) {
                    $fecha = $this->calcularVencimiento2($fechaInicio, $diaDePago);
                } else {
                    /// If there are several days of payment, we choose the closest date
                    $fechaTemp = $this->calcularVencimiento2($fechaInicio, $diaDePago);
                    if (strtotime($fechaTemp) < strtotime($fecha)) {
                        $fecha = $fechaTemp;
                    }
                }
            }
        }

        return $fecha;
    }

    /**
     * Aux recursive function to calcularVencimiento()
     *
     * @param string $fechaInicio
     * @param int    $diaDePago
     *
     * @return string
     */
    private function calcularVencimiento2($fechaInicio, $diaDePago = 0)
    {
        if ($diaDePago === 0) {
            return date('d-m-Y', strtotime($fechaInicio . ' ' . $this->vencimiento));
        }

        $fecha = date('d-m-Y', strtotime($fechaInicio . ' ' . $this->vencimiento));
        $tmpDia = date('d', strtotime($fecha));
        $tmpMes = date('m', strtotime($fecha));
        $tmpAnyo = date('Y', strtotime($fecha));

        if ($tmpDia > $diaDePago) {
            /// we calculate the collection day for the following month
            $fecha = date('d-m-Y', strtotime($fecha . ' +1 month'));
            $tmpMes = date('m', strtotime($fecha));
            $tmpAnyo = date('Y', strtotime($fecha));
        }

        /// now we choose a day, but what fits in the month, can not be February 31
        $tmpDia = min([$diaDePago, (int) date('t', strtotime($fecha))]);

        /// and finally we generated the date
        return date('d-m-Y', strtotime($tmpDia . '-' . $tmpMes . '-' . $tmpAnyo));
    }
}
