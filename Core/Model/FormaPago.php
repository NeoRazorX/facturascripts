<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Forma de pago de una factura, albarán, pedido o presupuesto.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FormaPago
{

    use Base\ModelTrait;

    /**
     * Clave primaria. Varchar (10).
     *
     * @var string
     */
    public $codpago;

    /**
     * Descripción de la forma de pago
     *
     * @var string
     */
    public $descripcion;

    /**
     * Pagados -> marca las facturas generadas como pagadas.
     *
     * @var string
     */
    public $genrecibos;

    /**
     * Código de la cuenta bancaria asociada.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Para indicar si hay que mostrar la cuenta bancaria del cliente.
     *
     * @var bool
     */
    public $domiciliado;

    /**
     * True (por defecto) -> mostrar los datos en documentos de venta,
     * incluida la cuenta bancaria asociada.
     *
     * @var bool
     */
    public $imprimir;

    /**
     * Sirve para generar la fecha de vencimiento de las facturas.
     *
     * @var string
     */
    public $vencimiento;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'formaspago';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codpago';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codpago = null;
        $this->descripcion = '';
        $this->genrecibos = 'Emitidos';
        $this->codcuenta = '';
        $this->domiciliado = false;
        $this->imprimir = true;
        $this->vencimiento = '+1day';
    }

    /**
     * Devuelve True si esta es la forma de pago predeterminada de la empresa
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codpago === $this->defaultItems->codPago();
    }

    /**
     * Comprueba la validez de los datos de la forma de pago.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);

        /// comprobamos la validez del vencimiento
        $fecha1 = date('d-m-Y');
        $fecha2 = date('d-m-Y', strtotime($this->vencimiento));
        if (strtotime($fecha1) > strtotime($fecha2)) {
            $this->miniLog->alert($this->i18n->trans('expiration-invalid'));

            return false;
        }

        return true;
    }

    /**
     * A partir de una fecha devuelve la nueva fecha de vencimiento en base a esta forma de pago.
     * Si se proporciona $diasDePago se usarán para la nueva fecha.
     *
     * @param string $fechaInicio
     * @param string $diasDePago  dias de pago específicos para el cliente (separados por comas).
     *
     * @return string
     */
    public function calcularVencimiento($fechaInicio, $diasDePago = '')
    {
        $fecha = $this->calcularVencimiento2($fechaInicio);

        /// validamos los días de pago
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
                    /// si hay varios dias de pago, elegimos la fecha más cercana
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
     * Crea la consulta necesaria para crear una nueva forma de pago en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . $this->tableName()
            . ' (codpago,descripcion,genrecibos,codcuenta,domiciliado,vencimiento)'
            . " VALUES ('CONT','Al contado','Pagados',null,false,'+0day')"
            . ",('TRANS','Transferencia bancaria','Emitidos',null,false,'+1month')"
            . ",('TARJETA','Tarjeta de crédito','Pagados',null,false,'+0day')"
            . ",('PAYPAL','PayPal','Pagados',null,false,'+0day');";
    }

    /**
     * Función recursiva auxiliar para calcularVencimiento()
     *
     * @param string  $fechaInicio
     * @param int $diaDePago
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
            /// calculamos el dia de cobro para el mes siguiente
            $fecha = date('d-m-Y', strtotime($fecha . ' +1 month'));
            $tmpMes = date('m', strtotime($fecha));
            $tmpAnyo = date('Y', strtotime($fecha));
        }

        /// ahora elegimos un dia, pero que quepa en el mes, no puede ser 31 de febrero
        $tmpDia = min([$diaDePago, (int) date('t', strtotime($fecha))]);

        /// y por último generamos la fecha
        return date('d-m-Y', strtotime($tmpDia . '-' . $tmpMes . '-' . $tmpAnyo));
    }
}
