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
class forma_pago extends \FacturaScripts\Core\Base\Model {

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
     * Constructor por defecto
     * @param array $f Array con los valores para crear una nueva forma de pago
     */
    public function __construct($f = FALSE) {
        parent::__construct('formaspago');
        if ($f) {
            $this->codpago = $f['codpago'];
            $this->descripcion = $f['descripcion'];
            $this->genrecibos = $f['genrecibos'];
            $this->codcuenta = $f['codcuenta'];
            $this->domiciliado = $this->str2bool($f['domiciliado']);
            $this->imprimir = $this->str2bool($f['imprimir']);
            $this->vencimiento = $f['vencimiento'];
        } else {
            $this->codpago = NULL;
            $this->descripcion = '';
            $this->genrecibos = 'Emitidos';
            $this->codcuenta = '';
            $this->domiciliado = FALSE;
            $this->imprimir = TRUE;
            $this->vencimiento = '+1day';
        }
    }
    
    /**
     * Crea la consulta necesaria para crear una nueva forma de pago en la base de datos.
     * @return string
     */
    public function install() {
        $this->clean_cache();
        return "INSERT INTO " . $this->table_name . " (codpago,descripcion,genrecibos,codcuenta,domiciliado,vencimiento)"
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
    public function is_default() {
        return ( $this->codpago == $this->default_items->codpago() );
    }

    /**
     * Devuelve la forma de pago con codpago = $cod
     * @param string $cod
     * @return \FacturaScripts\model\forma_pago|boolean
     */
    public function get($cod) {
        $pago = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codpago = " . $this->var2str($cod) . ";");
        if ($pago) {
            return new \forma_pago($pago[0]);
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve TRUE si la forma de pago existe
     * @return boolean
     */
    public function exists() {
        if (is_null($this->codpago)) {
            return FALSE;
        } else {
            return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codpago = " . $this->var2str($this->codpago) . ";");
        }
    }

    /**
     * Comprueba la validez de los datos de la forma de pago.
     */
    public function test() {
        $this->descripcion = $this->no_html($this->descripcion);

        /// comprobamos la validez del vencimiento
        $fecha1 = Date('d-m-Y');
        $fecha2 = Date('d-m-Y', strtotime($this->vencimiento));
        if (strtotime($fecha1) > strtotime($fecha2)) {
            /// vencimiento no válido, asignamos el predeterminado
            $this->miniLog->alert('Vencimiento no válido.');
            $this->vencimiento = '+1day';
        }
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        $this->clean_cache();
        $this->test();

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET descripcion = " . $this->var2str($this->descripcion) .
                    ", genrecibos = " . $this->var2str($this->genrecibos) .
                    ", codcuenta = " . $this->var2str($this->codcuenta) .
                    ", domiciliado = " . $this->var2str($this->domiciliado) .
                    ", imprimir = " . $this->var2str($this->imprimir) .
                    ", vencimiento = " . $this->var2str($this->vencimiento) .
                    "  WHERE codpago = " . $this->var2str($this->codpago) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (codpago,descripcion,genrecibos,codcuenta
            ,domiciliado,imprimir,vencimiento) VALUES 
                  (" . $this->var2str($this->codpago) .
                    "," . $this->var2str($this->descripcion) .
                    "," . $this->var2str($this->genrecibos) .
                    "," . $this->var2str($this->codcuenta) .
                    "," . $this->var2str($this->domiciliado) .
                    "," . $this->var2str($this->imprimir) .
                    "," . $this->var2str($this->vencimiento) . ");";
        }

        return $this->db->exec($sql);
    }

    /**
     * Elimina la forma de pago
     * @return boolean
     */
    public function delete() {
        $this->clean_cache();
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE codpago = " . $this->var2str($this->codpago) . ";");
    }

    /**
     * Limpia la caché
     */
    private function clean_cache() {
        $this->cache->delete('m_forma_pago_all');
    }

    /**
     * Devuelve un array con todas las formas de pago
     * @return \forma_pago
     */
    public function all() {
        /// Leemos la lista de la caché
        $listaformas = $this->cache->get_array('m_forma_pago_all');
        if (!$listaformas) {
            /// si no está en caché, buscamos en la base de datos
            $formas = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY descripcion ASC;");
            if ($formas) {
                foreach ($formas as $f) {
                    $listaformas[] = new \forma_pago($f);
                }
            }

            /// guardamos la lista en caché
            $this->cache->set('m_forma_pago_all', $listaformas);
        }

        return $listaformas;
    }

    /**
     * A partir de una fecha devuelve la nueva fecha de vencimiento en base a esta forma de pago.
     * Si se proporciona $dias_de_pago se usarán para la nueva fecha.
     * @param string $fecha_inicio
     * @param string $dias_de_pago dias de pago específicos para el cliente (separados por comas).
     * @return string
     */
    public function calcular_vencimiento($fecha_inicio, $dias_de_pago = '') {
        $fecha = $this->calcular_vencimiento2($fecha_inicio);

        /// validamos los días de pago
        $array_dias = array();
        foreach (str_getcsv($dias_de_pago) as $d) {
            if (intval($d) >= 1 && intval($d) <= 31) {
                $array_dias[] = intval($d);
            }
        }

        if ($array_dias) {
            foreach ($array_dias as $i => $dia_de_pago) {
                if ($i == 0) {
                    $fecha = $this->calcular_vencimiento2($fecha_inicio, $dia_de_pago);
                } else {
                    /// si hay varios dias de pago, elegimos la fecha más cercana
                    $fecha_temp = $this->calcular_vencimiento2($fecha_inicio, $dia_de_pago);
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
     */
    private function calcular_vencimiento2($fecha_inicio, $dia_de_pago = 0) {
        if ($dia_de_pago == 0) {
            return date('d-m-Y', strtotime($fecha_inicio . ' ' . $this->vencimiento));
        } else {
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
            $tmp_dia = min(array($dia_de_pago, intval(date('t', strtotime($fecha)))));

            /// y por último generamos la fecha
            return date('d-m-Y', strtotime($tmp_dia . '-' . $tmp_mes . '-' . $tmp_anyo));
        }
    }

}