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
 * Una divisa (moneda) con su símbolo y su tasa de conversión respecto al euro.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Divisa {

    use \FacturaScripts\Core\Base\Model {
        delete as private modelDelete;
    }

    /**
     * Clave primaria. Varchar (3).
     * @var string 
     */
    public $coddivisa;

    /**
     * Descripción de la divisa
     * @var string 
     */
    public $descripcion;

    /**
     * Tasa de conversión respecto al euro.
     * @var float
     */
    public $tasaconv;

    /**
     * Tasa de conversión respecto al euro (para compras).
     * @var float
     */
    public $tasaconvcompra;

    /**
     * código ISO 4217 en número: http://en.wikipedia.org/wiki/ISO_4217
     * @var string
     */
    public $codiso;

    /**
     * Símbolo que representa a la divisa
     * @var string 
     */
    public $simbolo;

    /**
     * Constructor por defecto
     * @param array $data Array con los valores para crear una nueva divisa
     */
    public function __construct($data = FALSE) {
        $this->init('divisas', 'coddivisa');
        if ($data) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear() {
        $this->coddivisa = NULL;
        $this->descripcion = '';
        $this->tasaconv = 1.00;
        $this->tasaconvcompra = 1.00;
        $this->codiso = NULL;
        $this->simbolo = '?';
    }

    /**
     * Crea la consulta necesaria para crear una nueva divisa en la base de datos.
     * @return string
     */
    public function install() {
        $this->cache->delete('m_divisa_all');
        return "INSERT INTO " . $this->tableName() . " (coddivisa,descripcion,tasaconv,tasaconvcompra,codiso,simbolo)"
                . " VALUES ('EUR','EUROS','1','1','978','€')"
                . ",('ARS','PESOS (ARG)','16.684','16.684','32','AR$')"
                . ",('CLP','PESOS (CLP)','704.0227','704.0227','152','CLP$')"
                . ",('COP','PESOS (COP)','3140.6803','3140.6803','170','CO$')"
                . ",('DOP','PESOS DOMINICANOS','49.7618','49.7618','214','RD$')"
                . ",('GBP','LIBRAS ESTERLINAS','0.865','0.865','826','£')"
                . ",('HTG','GOURDES','72.0869','72.0869','322','G')"
                . ",('MXN','PESOS (MXN)','23.3678','23.3678','484','MX$')"
                . ",('PAB','BALBOAS','1.128','1.128','590','B')"
                . ",('PEN','NUEVOS SOLES','3.736','3.736','604','S/.')"
                . ",('USD','DÓLARES EE.UU.','1.129','1.129','840','$')"
                . ",('VEF','BOLÍVARES','10.6492','10.6492','937','Bs')";
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url() {
        return 'index.php?page=admin_divisas';
    }

    /**
     * Devuelve TRUE si esta es la divisa predeterminada de la empresa
     * @return boolean
     */
    public function isDefault() {
        return ( $this->coddivisa == $this->defaultItems->codDivisa() );
    }

    /**
     * Devuelve la divisa con coddivsa = $cod
     * @param string $cod
     * @return boolean|divisa
     */
    public function get($cod) {
        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName() . " WHERE coddivisa = " . $this->var2str($cod) . ";");
        if ($data) {
            return new Divisa($data[0]);
        }

        return FALSE;
    }

    /**
     * Comprueba los datos de la divisa, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $status = FALSE;
        $this->descripcion = $this->noHtml($this->descripcion);
        $this->simbolo = $this->noHtml($this->simbolo);

        if (!preg_match("/^[A-Z0-9]{1,3}$/i", $this->coddivisa)) {
            $this->miniLog->alert($this->i18n->trans('bage-cod-invalid'));
        } else if (isset($this->codiso) && !preg_match("/^[A-Z0-9]{1,3}$/i", $this->codiso)) {
            $this->miniLog->alert($this->i18n->trans('iso-cod-invalid'));
        } else if ($this->tasaconv == 0) {
            $this->miniLog->alert($this->i18n->trans('conversion-rate-not-0'));
        } else if ($this->tasaconvcompra == 0) {
            $this->miniLog->alert($this->i18n->trans('conversion-rate-pruchases-not-0'));
        } else {
            $this->cache->delete('m_divisa_all');
            $status = TRUE;
        }

        return $status;
    }
    
    public function delete() {
        $this->cache->delete('m_divisa_all');
        return $this->modelDelete();
    }

    /**
     * Devuelve un array con todas las divisas.
     * @return divisa
     */
    public function all() {
        /// leemos de la cache
        $listad = $this->cache->get('m_divisa_all');
        if (!$listad) {
            /// si no está en cache, leemos de la base de datos
            $data = $this->dataBase->select("SELECT * FROM " . $this->tableName() . " ORDER BY coddivisa ASC;");
            if ($data) {
                foreach ($data as $d) {
                    $listad[] = new Divisa($d);
                }
            }

            /// guardamos en cache
            $this->cache->set('m_divisa_all', $listad);
        }

        return $listad;
    }

}
