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
 * A country, for example Spain.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Pais
{

    use Base\ModelTrait;

    /**
     * Primary key. Varchar(3).
     *
     * @var string Alpha-3 code of the country.
     *             http://es.wikipedia.org/wiki/ISO_3166-1
     */
    public $codpais;

    /**
     * Alpha-2 code of the country.
     * http://es.wikipedia.org/wiki/ISO_3166-1
     *
     * @var string
     */
    public $codiso;

    /**
     * Country name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'paises';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codpais';
    }

    /**
     * Returns True if the country is the default of the company.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codpais === AppSettings::get('default', 'codpais');
    }

    /**
     * Check the country's data, return True if they are correct.
     *
     * @return bool
     */
    public function test()
    {
        $this->codpais = trim($this->codpais);
        $this->nombre = self::noHtml($this->nombre);

        if (!preg_match('/^[A-Z0-9]{1,20}$/i', $this->codpais)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-country-code', ['%countryCode%' => $this->codpais]));

            return false;
        }

        if (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 100)) {
            self::$miniLog->alert(self::$i18n->trans('country-name-invalid'));

            return false;
        }

        return true;
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
