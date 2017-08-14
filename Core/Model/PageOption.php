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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\ExtendedController;

/**
 * Configuración visual de las vistas de FacturaScripts,
 * cada PageOption se corresponde con un controlador.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class PageOption
{

    use Base\ModelTrait {
        clear as clearTrait;
        loadFromData as loadFromDataTrait;
    }

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
     * Definición para tratamiento especial de filas
     * @var array
     */
    public $rows;

    /**
     * Definición de las columnas. Se denomina columns pero contiene
     * siempre GroupItem, el cual contiene las columnas.
     * @var array
     */
    public $columns;

    /**
     * Definición de filtros personalizados
     * @var array
     */
    public $filters;

    public function tableName()
    {
        return 'fs_pages_options';
    }

    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearTrait();
        $this->columns = [];
        $this->filters = [];
        $this->rows = [];
    }

    public function loadFromData($data)
    {
        $this->loadFromDataTrait($data);

        $columns = json_decode($data['columns'], true);
        $groupItem = new ExtendedController\GroupItem();
        $this->columns = $groupItem->loadFromJSON($columns);

        $rows = json_decode($data['rows'], true);
        $rowItem = new ExtendedController\RowItem();
        $this->rows = $rowItem->loadFromJSON($rows);
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     * @return bool
     */
    private function saveUpdate()
    {
        $columns = json_encode($this->columns);
        $filters = json_encode($this->filters);
        $rows = json_encode($this->rows);

        $sql = 'UPDATE ' . $this->tableName() . ' SET '
            . '  columns = ' . $this->var2str($columns)
            . ' ,filters = ' . $this->var2str($filters)
            . ' ,rows = ' . $this->var2str($rows)
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
        $rows = json_encode($this->rows);

        $sql = "INSERT INTO " . $this->tableName()
            . " (id, name, nick, columns, filters, rows) VALUES ("
            . "nextval('fs_pages_options_id_seq')" . ","
            . $this->var2str($this->name) . ","
            . $this->var2str($this->nick) . ","
            . $this->var2str($columns) . ","
            . $this->var2str($filters) . ","
            . $this->var2str($rows)
            . ");";

        if ($this->dataBase->exec($sql)) {
            $this->id = $this->dataBase->lastval();
            return true;
        }

        return false;
    }

    /**
     * Carga la estructura de columnas desde el XML
     * @param SimpleXMLElement $columns
     */
    private function getXMLGroupsColumns($columns)
    {
        // No hay agrupación de columnas
        if (empty($columns->group)) {
            $groupItem = new ExtendedController\GroupItem();
            $groupItem->loadFromXMLColumns($columns);
            $this->columns[] = $groupItem;
            unset($groupItem);
            return;
        }

        // Con agrupación de columnas
        foreach ($columns->group as $group) {
            $groupItem = new ExtendedController\GroupItem();
            $groupItem->loadFromXML($group);
            $this->columns[] = $groupItem;
            unset($groupItem);
        }
    }

    /**
     * Carga las condiciones especiales para las filas
     * desde el XML
     * @param SimpleXMLElement $rows
     */
    private function getXMLRows($rows)
    {
        foreach ($rows->row as $row) {
            $rowItem = new ExtendedController\RowItem();
            $rowItem->loadFromXML($row);
            $this->rows[$rowItem->type] = $rowItem;
            unset($rowItem);
        }
    }

    /**
     * Instala la configuración inicial de un controlador
     * @param string $name
     */
    public function installXML($name)
    {
        $this->id = null;
        $this->name = $name;
        $this->columns = [];
        $this->filters = [];
        $this->rows = [];
        $file = "Core/Controller/{$name}.xml";
        $xml = simplexml_load_file($file);

        if ($xml) {
            $this->getXMLGroupsColumns($xml->columns);

            if (!empty($xml->rows)) {
                $this->getXMLRows($xml->rows);
            }
//        $this->saveInsert();
        }
    }

    /**
     * Obtiene la configuración para el controlador y usuario
     * @param string $name
     * @param string $nick
     */
    public function getForUser($name, $nick)
    {
        $where = [];
        $where[] = new DataBase\DataBaseWhere('nick', $nick);
        $where[] = new DataBase\DataBaseWhere('nick', 'NULL', 'IS', 'OR');
        $where[] = new DataBase\DataBaseWhere('name', $name);

        $orderby = ['nick' => 'ASC'];

        $data = $this->all($where, $orderby, 0, 1);
        if (empty($data)) {
            $this->installXML($name);
            return;
        }

        $pageOption = $data[0];
        $this->id = $pageOption->id;
        $this->name = $pageOption->name;
        $this->nick = $pageOption->nick;
        $this->columns = $pageOption->columns;
        $this->filters = $pageOption->filters;
    }

    public function columnForField($fieldName)
    {
        $result = NULL;
        foreach ($this->columns as $group) {
            foreach ($group->columns as $column) {
                if ($column->widget->fieldName === $fieldName) {
                    $result = $column;
                    break;
                }
            }
            if (!empty($result)) {
                break;
            }
        }
        return $result;
    }
}
