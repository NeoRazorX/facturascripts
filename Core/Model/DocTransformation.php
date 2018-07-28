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
 * A model to manage the transformations of documents. For example aprobe order to delibery note.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 * @author Rafael San José Tovar <rafael.sanjose@x-netdigital.com>
 */
class DocTransformation extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key. Autoincremental.
     *
     * @var int
     */
    public $id;

    /**
     * id of document 1
     *
     * @var int
     */
    public $iddoc1;

    /**
     * id of document 2
     *
     * @var int
     */
    public $iddoc2;

    /**
     * id of the line in document 1
     *
     * @var int
     */
    public $idlinea1;

    /**
     * id of the line in document 2
     *
     * @var int
     */
    public $idlinea2;

    /**
     * Name of model1. Varchar(30)
     *
     * @var string
     */
    public $model1;

    /**
     * Name of model2. Varchar(30)
     *
     * @var string
     */
    public $model2;

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
        return 'doctransformations';
    }
}
