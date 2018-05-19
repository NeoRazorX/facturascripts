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
namespace FacturaScripts\Core\Model;

*/
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;

/**
 * A division of acoounting seats in different books
 *
 * @author Raul Jimenez <raul.jimenez@nazcanetworks.com>
 */
class Diario extends Base\ModelClass
{
    use Base\ModelTrait;
    
    /** Primary key. varchar (4)
     * 
     * @var string
     */
    public $coddiario;
    
    /** Name os accounting book
     * 
     * @var string
     */
    public $nombre;
    
    public function clear(){
        parent::clear();
    }
    
    public static function primaryColumn()
    {
        return 'coddiario';
    }
    public static function tableName(){
        return 'diarios';
    }
    public function test()
    {
        $this->coddiario = trim($this->coddiario);
        $this->nombre = Utils::noHtml($this->nombre);

        if (!preg_match('/^[A-Z0-9]{1,4}$/i', $this->coddiario)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'coddiario', '%min%' => '1', '%max%' => '4']));
            return false;
        }

        if (strlen($this->nombre) < 1 || strlen($this->nombre) > 100) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'nombre', '%min%' => '1', '%max%' => '100']));
            return false;
        }

        return parent::test();
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
        return 'INSERT INTO ' . static::tableName() . ' (coddiario,nombre) '
            . " VALUES "
            . "('1','Principal') "
            . "('2','Diario de Facturas')"
            . "('3','Cartera de pagos')"
            . "('4','Cartera de cbros')";
        
    }
    
}
