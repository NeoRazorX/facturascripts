<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2013-2019  Carlos Garcia Gomez     <carlos@facturascripts.com>
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
 * A province.
 *
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 */
class Provincia extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Code id
     *
     * @var string
     */
    public $codeid;

    /**
     * 'Normalized' code in Spain to identify the provinces.
     *
     * @url: https://es.wikipedia.org/wiki/Provincia_de_España#Denominaci.C3.B3n_y_lista_de_las_provincias
     *
     * @var string
     */
    public $codisoprov;

    /**
     * Country code associated with the province.
     *
     * @var string
     */
    public $codpais;

    /**
     * Identify the registry.
     *
     * @var string
     */
    public $idprovincia;

    /**
     * Name of the province.
     *
     * @var string
     */
    public $provincia;

    public function clear()
    {
        parent::clear();
        $this->codpais = $this->toolBox()->appSettings()->get('default', 'codpais');
    }

    /**
     *
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Pais();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idprovincia';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'provincias';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->provincia = $this->toolBox()->utils()->noHtml($this->provincia);
        return parent::test();
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
