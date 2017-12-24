<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017       Francesc Pineda Segarra     <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2013-2017  Carlos Garcia Gomez         <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Import\CSVImport;

/**
 * A province.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class Provincia
{

    use Base\ModelTrait {
        url as private traitUrl;
    }

    /**
     * Identify the registry.
     *
     * @var string
     */
    public $idprovincia;

    /**
     * Country code associated with the province.
     *
     * @var string
     */
    public $codpais;

    /**
     * Name of the province.
     *
     * @var string
     */
    public $provincia;

    /**
     * 'Normalized' code in Spain to identify the provinces.
     * @url: https://es.wikipedia.org/wiki/Provincia_de_España#Denominaci.C3.B3n_y_lista_de_las_provincias
     *
     * @var string
     */
    public $codisoprov;

    /**
     * Postal code associated with the province.
     * @url: https://upload.wikimedia.org/wikipedia/commons/5/5c/2_digit_postcode_spain.png
     *
     * @var string
     */
    public $codpostal2d;

    /**
     * Latitude associated with the place.
     *
     * @var float
     */
    public $latitud;

    /**
     * Length associated with the place.
     *
     * @var float
     */
    public $longitud;

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
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idprovincia';
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

    /**
     * Returns the url where to see/modify the data.
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        return $this->traitUrl($type, 'ListPais&active=List');
    }
}
