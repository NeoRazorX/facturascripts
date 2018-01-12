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
 * The warehouse where the items are physically.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Almacen
{

    use Base\ModelTrait;
    use Base\ContactInformation;

    /**
     * Primary key. Varchar (4).
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Store name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Store contact person.
     *
     * @var string
     */
    public $contacto;

    /**
     * Still unused.
     *
     * @var string
     */
    public $observaciones;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'almacenes';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codalmacen';
    }

    public function primaryDescriptionColumn() 
    {
        return 'nombre';
    }

    /**
     * Returns True if is the default wharehouse for the company.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codalmacen === AppSettings::get('default', 'codalmacen');
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codalmacen = trim($this->codalmacen);
        $this->nombre = self::noHtml($this->nombre);
        $this->provincia = self::noHtml($this->provincia);
        $this->poblacion = self::noHtml($this->poblacion);
        $this->direccion = self::noHtml($this->direccion);
        $this->codpostal = self::noHtml($this->codpostal);
        $this->telefono = self::noHtml($this->telefono);
        $this->fax = self::noHtml($this->fax);
        $this->contacto = self::noHtml($this->contacto);

        if (!preg_match('/^[A-Z0-9]{1,4}$/i', $this->codalmacen)) {
            self::$miniLog->alert(self::$i18n->trans('store-cod-invalid'));
        } elseif (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 100)) {
            self::$miniLog->alert(self::$i18n->trans('store-name-invalid'));
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
