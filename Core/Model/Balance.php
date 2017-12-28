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

use FacturaScripts\Core\Lib\Import\CSVImport;

/**
 * Defines which accounts must be used to generate the different accounting reports.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Balance
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codbalance;

    /**
     * Description 4 of the balance.
     *
     * @var string
     */
    public $descripcion4ba;

    /**
     * Description 4 of the balance.
     *
     * @var string
     */
    public $descripcion4;

    /**
     * Level 4 of the balance.
     *
     * @var string
     */
    public $nivel4;

    /**
     * Description 3 of the balance.
     *
     * @var string
     */
    public $descripcion3;

    /**
     * Order 3 of the balance.
     *
     * @var string
     */
    public $orden3;

    /**
     * Level 2 of the balance.
     *
     * @var string
     */
    public $nivel3;

    /**
     * Description 2 of the balance.
     *
     * @var string
     */
    public $descripcion2;

    /**
     * Level 2 of the balance.
     *
     * @var int
     */
    public $nivel2;

    /**
     * Description 1 of the balance.
     *
     * @var string
     */
    public $descripcion1;

    /**
     * Level 1 of the balance.
     *
     * @var string
     */
    public $nivel1;

    /**
     * Nature of the balance.
     *
     * @var string
     */
    public $naturaleza;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_codbalances08';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codbalance';
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
     * Test model's data.
     * 
     * @return bool
     */
    public function test()
    {
        $this->descripcion1 = self::noHtml($this->descripcion1);
        $this->descripcion2 = self::noHtml($this->descripcion2);
        $this->descripcion3 = self::noHtml($this->descripcion3);
        $this->descripcion4 = self::noHtml($this->descripcion4);
        $this->descripcion4ba = self::noHtml($this->descripcion4ba);
        
        return true;
    }
}
