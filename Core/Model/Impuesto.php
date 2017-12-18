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
 * A tax (VAT) that can be associated to articles, delivery notes lines,
 * invoices, etc.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Impuesto
{

    use Base\ModelTrait;

    /**
     * Primary key. varchar(10).
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Sub-account code for sales.
     *
     * @var string
     */
    public $codsubcuentarep;

    /**
     * Sub-account code for purchases.
     *
     * @var string
     */
    public $codsubcuentasop;

    /**
     * Description of the tax.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Value of VAT.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Value of the surcharge.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'impuestos';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codimpuesto';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->codimpuesto = null;
        $this->codsubcuentarep = null;
        $this->codsubcuentasop = null;
        $this->descripcion = null;
        $this->iva = 0.0;
        $this->recargo = 0.0;
    }

    /**
     * Returns True if is the default tax for the user.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codimpuesto === AppSettings::get('default', 'codimpuesto');
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codimpuesto = trim($this->codimpuesto);
        $this->descripcion = self::noHtml($this->descripcion);

        if (empty($this->codimpuesto) || strlen($this->codimpuesto) > 10) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-tax-code-length'));
        } elseif (empty($this->descripcion) || strlen($this->descripcion) > 50) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-description-tax'));
        } else {
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
