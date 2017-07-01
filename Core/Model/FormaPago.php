<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FormaPago {

    use \FacturaScripts\Core\Base\Model;

    /**
     * Clave primaria. Varchar (10).
     * @var string
     */
    public $codpago;

    /**
     * Descripción de la forma de pago
     * @var string
     */
    public $descripcion;

    /**
     * Pagados -> marca las facturas generadas como pagadas.
     * @var string
     */
    public $genrecibos;

    /**
     * Código de la cuenta bancaria asociada.
     * @var string
     */
    public $codcuenta;

    /**
     * Para indicar si hay que mostrar la cuenta bancaria del cliente.
     * @var boolean
     */
    public $domiciliado;

    /**
     * TRUE (por defecto) -> mostrar los datos en documentos de venta,
     * incluida la cuenta bancaria asociada.
     * @var boolean
     */
    public $imprimir;

    /**
     * Sirve para generar la fecha de vencimiento de las facturas.
     * @var string
     */
    public $vencimiento;

    /**
     * FormaPago constructor.
     *
     * @param bool $data
     *
     * @throws \RuntimeException
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     */
    public function __construct($data = FALSE) {
        $this->init(__CLASS__, 'formaspago', 'codpago');
        if ($data) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear() {
        $this->codpago = NULL;
        $this->descripcion = '';
        $this->genrecibos = 'Emitidos';
        $this->codcuenta = '';
        $this->domiciliado = FALSE;
        $this->imprimir = TRUE;
        $this->vencimiento = '+1day';
    }

    /**
     * Crea la consulta necesaria para crear una nueva forma de pago en la base de datos.
     * @return string
     */
    public function install() {
        return 'INSERT INTO ' . $this->tableName() . ' (codpago,descripcion,genrecibos,codcuenta,domiciliado,vencimiento)'
                . " VALUES ('CONT','Al contado','Pagados',NULL,FALSE,'+0day')"
                . ",('TRANS','Transferencia bancaria','Emitidos',NULL,FALSE,'+1month')"
                . ",('TARJETA','Tarjeta de crédito','Pagados',NULL,FALSE,'+0day')"
                . ",('PAYPAL','PayPal','Pagados',NULL,FALSE,'+0day');";
    }

    /**
     * Devuelve la URL donde ver/modificar los datos
     * @return string
     */
    public function url() {
        return 'index.php?page=contabilidad_formas_pago';
    }

    /**
     * Devuelve TRUE si esta es la forma de pago predeterminada de la empresa
     * @return boolean
     */
    public function isDefault() {
        return ( $this->codpago === $this->defaultItems->codPago() );
    }

    /**
     * Comprueba la validez de los datos de la forma de pago.
     * @return boolean
     * @throws \Symfony\Component\Translation\Exception\InvalidArgumentException
     */
    public function test() {
        $this->descripcion = static::noHtml($this->descripcion);

        /// comprobamos la validez del vencimiento
        $fecha1 = date('d-m-Y');
        $fecha2 = date('d-m-Y', strtotime($this->vencimiento));
        if (strtotime($fecha1) > strtotime($fecha2)) {
            $this->miniLog->alert($this->i18n->trans('expiration-invalid'));
            return FALSE;
        }

        return TRUE;
    }

    /**
     * A partir de una fecha devuelve la nueva fecha de vencimiento en base a esta forma de pago.
     * Si se proporciona $dias_de_pago se usarán para la nueva fecha.
     * @param string $fecha_inicio
     * @param string $dias_de_pago dias de pago específicos para el cliente (separados por comas).
     * @return string
     */
    public function calcularVencimiento($fecha_inicio, $dias_de_pago = '') {
        $fecha = $this->calcularVencimiento2($fecha_inicio);

        /// validamos los días de pago
        $array_dias = array();
        foreach (str_getcsv($dias_de_pago) as $d) {
            if ((int)$d >= 1 && (int)$d <= 31) {
                $array_dias[] = (int)$d;
            }
        }

        if ($array_dias !== NULL) {
            foreach ($array_dias as $i => $dia_de_pago) {
                if ($i === 0) {
                    $fecha = $this->calcularVencimiento2($fecha_inicio, $dia_de_pago);
                } else {
                    /// si hay varios dias de pago, elegimos la fecha más cercana
                    $fecha_temp = $this->calcularVencimiento2($fecha_inicio, $dia_de_pago);
                    if (strtotime($fecha_temp) < strtotime($fecha)) {
                        $fecha = $fecha_temp;
                    }
                }
            }
        }

        return $fecha;
    }

    /**
     * Función recursiva auxiliar para calcular_vencimiento()
     * @param string $fecha_inicio
     * @param string|integer $dia_de_pago
     * @return string
     */
    private function calcularVencimiento2($fecha_inicio, $dia_de_pago = 0) {
        if ($dia_de_pago === 0) {
            return date('d-m-Y', strtotime($fecha_inicio . ' ' . $this->vencimiento));
        }

        $fecha = date('d-m-Y', strtotime($fecha_inicio . ' ' . $this->vencimiento));
        $tmp_dia = date('d', strtotime($fecha));
        $tmp_mes = date('m', strtotime($fecha));
        $tmp_anyo = date('Y', strtotime($fecha));

        if ($tmp_dia > $dia_de_pago) {
            /// calculamos el dia de cobro para el mes siguiente
            $fecha = date('d-m-Y', strtotime($fecha . ' +1 month'));
            $tmp_mes = date('m', strtotime($fecha));
            $tmp_anyo = date('Y', strtotime($fecha));
        }

        /// ahora elegimos un dia, pero que quepa en el mes, no puede ser 31 de febrero
        $tmp_dia = min(array($dia_de_pago, (int)date('t', strtotime($fecha))));

        /// y por último generamos la fecha
        return date('d-m-Y', strtotime($tmp_dia . '-' . $tmp_mes . '-' . $tmp_anyo));
    }
}
