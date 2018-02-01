<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * A supplier. It can be related to several addresses or sub-accounts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Proveedor extends Base\ComercialContact
{
    use Base\ModelTrait;

    /**
     * True -> the supplier is a creditor, that is, we do not buy him merchandise,
     * we buy services, etc.
     *
     * @var bool
     */
    public $acreedor;
    
    /**
     * Transport tax.
     *
     * @var string
     */
    public $codimpuestoportes;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'proveedores';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codproveedor';
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
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->acreedor = false;
        $this->codimpuestoportes = AppSettings::get('default', 'codimpuesto');
    }

    /**
     * Returns the addresses associated with the provider.
     *
     * @return DireccionProveedor[]
     */
    public function getDirecciones()
    {
        $dirModel = new DireccionProveedor();

        return $dirModel->all([new DataBaseWhere('codproveedor', $this->codproveedor)]);
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        parent::test();
        $this->codproveedor = empty($this->codproveedor) ? (string) $this->newCode() : trim($this->codproveedor);

        return true;
    }
}
