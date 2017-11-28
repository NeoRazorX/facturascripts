<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Un grupo de clientes, que puede estar asociado a una tarifa.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class GrupoClientes
{

    use Base\ModelTrait {
        url as private traitURL;
    }

    /**
     * Clave primaria
     *
     * @var string
     */
    public $codgrupo;

    /**
     * Nombre del grupo
     *
     * @var string
     */
    public $nombre;

    /**
     * Código de la tarifa asociada, si la hay
     *
     * @var string
     */
    public $codtarifa;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'gruposclientes';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codgrupo';
    }

    /**
     * Devuelve un nuevo código para un nuevo grupo de clientes
     *
     * @return string
     */
    public function getNewCodigo()
    {
        if (strtolower(FS_DB_TYPE) === 'postgresql') {
            $sql = 'SELECT codgrupo from ' . static::tableName() . " WHERE codgrupo ~ '^\d+$'"
                . ' ORDER BY codgrupo::integer DESC';
        } else {
            $sql = 'SELECT codgrupo from ' . static::tableName() . " WHERE codgrupo REGEXP '^[0-9]+$'"
                . ' ORDER BY CAST(`codgrupo` AS decimal) DESC';
        }

        $data = $this->dataBase->selectLimit($sql, 1);
        if (!empty($data)) {
            return sprintf('%06s', 1 + (int) $data[0]['codgrupo']);
        }

        return '000001';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->nombre = self::noHtml($this->nombre);

        return true;
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
        /// como hay una clave ajena a tarifas, tenemos que comprobar esa tabla antes
        //new Tarifa();

        return '';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        return $this->traitURL($type, 'ListCliente&active=List');
    }
}
