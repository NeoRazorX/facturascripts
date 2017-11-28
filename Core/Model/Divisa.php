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

use FacturaScripts\Core\App\AppSettings;

/**
 * Una divisa (moneda) con su símbolo y su tasa de conversión respecto al euro.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Divisa
{

    use Base\ModelTrait;

    /**
     * Primary key. Varchar (3).
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Descripción de la divisa
     *
     * @var string
     */
    public $descripcion;

    /**
     * Tasa de conversión respecto al euro.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Tasa de conversión respecto al euro (para compras).
     *
     * @var float|int
     */
    public $tasaconvcompra;

    /**
     * código ISO 4217 en número: http://en.wikipedia.org/wiki/ISO_4217
     *
     * @var string
     */
    public $codiso;

    /**
     * Símbolo que representa a la divisa
     *
     * @var string
     */
    public $simbolo;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'divisas';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'coddivisa';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->coddivisa = null;
        $this->descripcion = '';
        $this->tasaconv = 1.00;
        $this->tasaconvcompra = 1.00;
        $this->codiso = null;
        $this->simbolo = '?';
    }

    /**
     * Returns True if is the default currency for the company.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->coddivisa === AppSettings::get('default', 'coddivisa');
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;
        $this->descripcion = self::noHtml($this->descripcion);
        $this->simbolo = self::noHtml($this->simbolo);

        if (!preg_match('/^[A-Z0-9]{1,3}$/i', $this->coddivisa)) {
            $this->miniLog->alert($this->i18n->trans('bage-cod-invalid'));
        } elseif ($this->codiso !== null && !preg_match('/^[A-Z0-9]{1,3}$/i', $this->codiso)) {
            $this->miniLog->alert($this->i18n->trans('iso-cod-invalid'));
        } elseif ($this->tasaconv === 0) {
            $this->miniLog->alert($this->i18n->trans('conversion-rate-not-0'));
        } elseif ($this->tasaconvcompra === 0) {
            $this->miniLog->alert($this->i18n->trans('conversion-rate-pruchases-not-0'));
        } else {
            $this->cache->delete('m_divisa_all');
            $status = true;
        }

        return $status;
    }

    /**
     * Crea la consulta necesaria para crear una nueva divisa en la base de datos.
     *
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . static::tableName() . ' (coddivisa,descripcion,tasaconv,tasaconvcompra,codiso,simbolo)'
            . " VALUES ('EUR','EUROS','1','1','978','€')"
            . ",('ARS','PESOS ARGENTINOS','16.684','16.684','32','AR$')"
            . ",('CLP','PESOS CHILENOS','704.0227','704.0227','152','CLP$')"
            . ",('COP','PESOS COLOMBIANOS','3140.6803','3140.6803','170','CO$')"
            . ",('DOP','PESOS DOMINICANOS','49.7618','49.7618','214','RD$')"
            . ",('GBP','LIBRAS ESTERLINAS','0.865','0.865','826','£')"
            . ",('HTG','GOURDES','72.0869','72.0869','322','G')"
            . ",('MXN','PESOS MEXICANO','23.3678','23.3678','484','MX$')"
            . ",('PAB','BALBOAS','1.128','1.128','590','B')"
            . ",('PEN','NUEVOS SOLES','3.736','3.736','604','S/.')"
            . ",('USD','DÓLARES EE.UU.','1.129','1.129','840','$')"
            . ",('VEF','BOLÍVARES','10.6492','10.6492','937','Bs')";
    }
}
