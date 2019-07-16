<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Ciudad
 * 
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Frank Aguirre        <faguirre@soenac.com>
 */
class Ciudad extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Ciudad
     *
     * @var string
     */
    public $ciudad;

    /**
     * Code id
     *
     * @var string
     */
    public $codeid;

    /**
     * Id ciudad
     *
     * @var int
     */
    public $idciudad;

    /**
     * Id provincia
     *
     * @var int
     */
    public $idprovincia;

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependency
        new Provincia();

        return parent::install();
    }

    /**
     * Primary column
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idciudad';
    }

    /**
     * Table name
     *
     * @return string
     */
    public static function tableName()
    {
        return 'ciudades';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List')
    {
        return parent::url($type, $list);
    }
}
