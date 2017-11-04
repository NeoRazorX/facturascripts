<?php
/**
 * This file is part of facturacion_base
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

    use Base\ModelTrait;

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
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'gruposclientes';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
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
            $sql = "SELECT codgrupo from " . $this->tableName() . " where codgrupo ~ '^\d+$'"
                . " ORDER BY codgrupo::integer DESC";
        } else {
            $sql = "SELECT codgrupo from " . $this->tableName() . " where codgrupo REGEXP '^[0-9]+$'"
                . " ORDER BY CAST(`codgrupo` AS decimal) DESC";
        }

        $data = $this->dataBase->selectLimit($sql, 1, 0);
        if (!empty($data)) {
            return sprintf('%06s', (1 + (int) $data[0]['codgrupo']));
        }

        return '000001';
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $this->nombre = self::noHtml($this->nombre);

        return true;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
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
     * Devuelve la url donde ver/modificar los datos
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        $value = $this->primaryColumnValue();
        $model = $this->modelClassName();
        $result = 'index.php?page=';
        switch ($type) {
            case 'list':
                $result .= 'ListCliente&active=List' . $model;
                break;

            case 'edit':
                $result .= 'Edit' . $model . '&code=' . $value;
                break;

            case 'new':
                $result .= 'Edit' . $model;
                break;

            default:
                $result .= empty($value) ? 'ListCliente&active=List' . $model : 'Edit' . $model . '&code=' . $value;
                break;
        }

        return $result;
    }
}
