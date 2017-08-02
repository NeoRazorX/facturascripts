<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use FacturaScripts\Core\Base\Database as DataBase;
use FacturaScripts\Core\Base\ViewController as ViewController;

/**
 * Configuración visual de las vistas de FacturaScripts,
 * cada PageOption se corresponde con un controlador.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class PageOption
{

    use Base\ModelTrait;

    public $id;

    /**
     * Nombre de la página (controlador).
     * @var string
     */
    public $name;

    /**
     * Identificador del Usuario.
     * @var string
     */
    public $nick;

    /**
     * Definición de las columnas
     * @var array
     */
    public $columns;

    /**
     * Definición de filtros personalizados
     * @var array
     */
    public $filters;

    /**
     * Page constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'fs_pages_options', 'id');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->id = null;
        $this->name = null;
        $this->nick = null;
        $this->columns = [];
        $this->filters = [];
    }

    public function loadFromData($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->nick = $data['nick'];

        $columns = json_decode($data['columns'], true);
        $columnItem = new ViewController\ColumnItem();
        $this->columns = $columnItem->columnsFromJSON($columns);

        // TODO: Cargar estructura de filtros
        $filters = json_decode($data['filters'], true);
        $this->filters = $filters;
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     * @return bool
     */
    private function saveUpdate()
    {
        $columns = json_encode($this->columns);
        $filters = json_encode($this->filters);

        $sql = 'UPDATE ' . $this->tableName() . ' SET '
            . '  columns = ' . $this->var2str($columns)
            . ' ,filters = ' . $this->var2str($filters)
            . ' WHERE id = ' . $this->id . ';';
        return $this->dataBase->exec($sql);
    }

    /**
     * Inserta los datos del modelo en la base de datos.
     * @return bool
     */
    private function saveInsert()
    {
        $columns = json_encode($this->columns);
        $filters = json_encode($this->filters);

        $sql = "INSERT INTO " . $this->tableName()
            . " (id, name, nick, columns, filters) VALUES ("
            . "nextval('fs_pages_options_id_seq')" . ","
            . $this->var2str($this->name) . ","
            . $this->var2str($this->nick) . ","
            . $this->var2str($columns) . ","
            . $this->var2str($filters)
            . ");";

        if ($this->dataBase->exec($sql)) {
            $this->id = $this->dataBase->lastval();
            return true;
        }

        return false;
    }

    public function installXML($name)
    {
        $this->id = null;
        $this->name = $name;
        $this->columns = [];
        $this->filters = [];
        $file = "Dinamic/Controller/{$name}.xml";
        $xml = simplexml_load_file($file);

        if ($xml) {
            $columnItem = new ViewController\ColumnItem();
            foreach ($xml->columns->column as $column) {
                $columnItem->loadFromXMLColumn($column);
                $this->columns[$columnItem->field->name] = $columnItem;
            }
            unset($columnItem);

//        foreach ($xml->filters->filter as $filter) {
//        }
        }
        $this->saveInsert();
    }

    public function getForUser($name, $nick)
    {
        $where = [];
        $where[] = new DataBase\DataBaseWhere('name', $name);
        $where[] = new DataBase\DataBaseWhere('nick', $nick);
        $where[] = new DataBase\DataBaseWhere('nick', 'NULL', 'IS', 'OR');

        $orderby = ['nick' => 'ASC'];

        $data = $this->all($where, $orderby, 0, 1);
        if (empty($data)) {
            $this->installXML($name);
        } else {
            $pageOption = $data[0];
            $this->id = $pageOption->id;
            $this->name = $pageOption->name;
            $this->nick = $pageOption->nick;
            $this->columns = $pageOption->columns;
            $this->filters = $pageOption->filters;
        }
    }
}
