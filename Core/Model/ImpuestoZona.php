<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * A tax (VAT) that can be associated to tax, country, province, and.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class ImpuestoZona extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key autoincremental
     *
     * @var int
     */
    public $id;

    /**
     * Foreign key with tax table. varchar(10).
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Foreign key with country table. varchar(20).
     *
     * @var string
     */
    public $codpais;

    /**
     * Foreign key with provincias table. varchar(20).
     *
     * @var string
     */
    public $codisopro;

    /**
     * Foreign key with tax table. varchar(10).
     *
     * @var string
     */
    public $codimpuestosel;

    /**
     * Priority of taxt by zone.
     *
     * @var int
     */
    public $prioridad;

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'impuestoszonas';
    }
}
