<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016       Joe Nilson             <joenilson at gmail.com>
 * Copyright (C) 2017-2018  Carlos García Gómez    <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
 * Define a permission package to quickly assign users.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class Variante extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary Key, autoincremental.
     *
     * @var int
     */
    public $idvariante;

    /**
     * Reference of the variant. Maximun 30 characteres.
     *
     * @var string
     */
    public $referencia;

    /**
     * Barcode. Maximun 20 characteres.
     *
     * @var string
     */
    public $codbarras;

    /**
     * Price of the variant
     *
     * @var double
     */
    public $pvp;

    /**
     * Foreign key of table atributo_valores.
     *
     * @var int
     */
    public $idatributovalor1;

    /**
     * Foreign key of table atributo_valores.
     *
     * @var int
     */
    public $idatributovalor2;

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idvariante';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'variantes';
    }
}
