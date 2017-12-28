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
use FacturaScripts\Core\Lib\Import\CSVImport;

/**
 * A currency with its symbol and its conversion rate with respect to the euro.
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
     * Currency description.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Conversion rate to the euro.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Conversion rate to the euro (for purchases).
     *
     * @var float|int
     */
    public $tasaconvcompra;

    /**
     * ISO 4217 code in number: http://en.wikipedia.org/wiki/ISO_4217
     *
     * @var string
     */
    public $codiso;

    /**
     * Symbol representing the currency.
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
            self::$miniLog->alert(self::$i18n->trans('bage-cod-invalid'));
        } elseif ($this->codiso !== null && !preg_match('/^[A-Z0-9]{1,3}$/i', $this->codiso)) {
            self::$miniLog->alert(self::$i18n->trans('iso-cod-invalid'));
        } elseif ($this->tasaconv === 0) {
            self::$miniLog->alert(self::$i18n->trans('conversion-rate-not-0'));
        } elseif ($this->tasaconvcompra === 0) {
            self::$miniLog->alert(self::$i18n->trans('conversion-rate-pruchases-not-0'));
        } else {
            self::$cache->delete('m_divisa_all');
            $status = true;
        }

        return $status;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        return CSVImport::importTableSQL(static::tableName());
    }
}
