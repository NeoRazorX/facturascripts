<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;

/**
 * Payment method for company and exercise.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class FormaPagoEjercicio extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Code of the associated bank account.
     *
     * @var string
     */
    public $codcuentabanco;

    /**
     * Exercise code of the accounting entry.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Foreign Key with FormasPago table.
     *
     * @var string
     */
    public $codpago;

    /**
     * Sub-account code.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Foreign Key with Empresas table.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Primary key. Serial.
     *
     * @var type
     */
    public $idfpagoejer;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->idempresa = AppSettings::get('default', 'idempresa');
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
        new Ejercicio();
        new FormaPago();
        new CuentaBanco();
        return parent::install();
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idfpagoejer';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'formaspagoejercicios';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        return parent::test();
    }
}
