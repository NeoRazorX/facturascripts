<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Utils;

/**
 * The warehouse where the items are physically.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Almacen extends Base\Address
{

    use Base\ModelTrait;

    /**
     * Primary key. Varchar (4).
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Foreign Key with Empresas table.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Store name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Store phone number.
     *
     * @var string
     */
    public $telefono;

    /**
     * Removed warehouse from database.
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->isDefault()) {
            self::$miniLog->alert(self::$i18n->trans('cant-delete-default-warehouse'));
            return false;
        }

        return parent::delete();
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Empresa();
        return parent::install();
    }

    /**
     * Returns True if this is the default wharehouse.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codalmacen === AppSettings::get('default', 'codalmacen');
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codalmacen';
    }

    /**
     * Returns the description of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombre';
    }

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
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codalmacen = empty($this->codalmacen) ? (string) $this->newCode() : trim($this->codalmacen);
        if (!preg_match('/^[A-Z0-9_\+\.\-]{1,4}$/i', $this->codalmacen)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-alphanumeric-code', ['%value%' => $this->codalmacen, '%column%' => 'codalmacen', '%min%' => '1', '%max%' => '4']));
            return false;
        }

        $this->nombre = Utils::noHtml($this->nombre);
        $this->telefono = Utils::noHtml($this->telefono);
        return parent::test();
    }
}
