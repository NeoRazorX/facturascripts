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

    /**
     * Identificador
     *
     * @var int
     */
    public $id;

    /**
     * Nombre de la página (controlador).
     *
     * @var string
     */
    public $name;

    /**
     * Identificador del Usuario.
     *
     * @var string
     */
    public $nick;

    /**
     * Definición para tratamiento especial de filas
     *
     * @var array
     */
    public $rows;

    /**
     * Definición de las columnas. Se denomina columns pero contiene
     * siempre GroupItem, el cual contiene las columnas.
     *
     * @var array
     */
    public $columns;

    /**
     * Definición de filtros personalizados
     *
     * @var array
     */
    public $filters;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public function tableName()
    {
        return 'fs_pages_options';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
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
        /// necesitamos estas clase para las claves ajenas
        new Page();
        new User();

        return '';
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

    /**
     * Carga los datos desde un array
     *
     * @param array $data
     */
    public function loadFromData($data)
    {
        $this->loadFromDataTrait($data, ['columns', 'filters', 'rows']);

        $groups = json_decode($data['columns'], true);
        foreach ($groups as $item) {
            $groupItem = new ExtendedController\GroupItem();
            $groupItem->loadFromJSON($item);

            $this->columns[$groupItem->name] = $groupItem;
            unset($groupItem);
        }

        $rows = json_decode($data['rows'], true);
        if (!empty($rows)) {
            foreach ($rows as $item) {
                $rowItem = new ExtendedController\RowItem();
                $rowItem->loadFromJSON($item);
                $this->rows[$rowItem->type] = $rowItem;
                unset($rowItem);
            }
        }
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     *
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
     *
     * @return bool
     */
    private function saveInsert()
    {
        $columns = json_encode($this->columns);
        $filters = json_encode($this->filters);
        $rows = json_encode($this->rows);

        $sql = 'INSERT INTO ' . $this->tableName()
            . ' (id, name, nick, columns, filters, rows) VALUES ('
            . "nextval('fs_pages_options_id_seq')" . ','
            . $this->var2str($this->name) . ','
            . $this->var2str($this->nick) . ','
            . $this->var2str($columns) . ','
            . $this->var2str($filters) . ','
            . $this->var2str($rows)
            . ');';

        if ($this->dataBase->exec($sql)) {
            $lastVal = $this->dataBase->lastval();
            if ($lastVal === FALSE) {
                return false;
            }

            $this->id = $lastVal;
            return true;
        }

        return false;
    }

    /**
     * Carga la estructura de columnas desde el XML
     *
     * @param \SimpleXMLElement $columns
     */
    private function getXMLGroupsColumns($columns)
    {
        // No hay agrupación de columnas
        if (empty($columns->group)) {
            $groupItem = new ExtendedController\GroupItem();
            $groupItem->loadFromXMLColumns($columns);
            $this->columns[$groupItem->name] = $groupItem;
            unset($groupItem);

            return;
        }

        // Con agrupación de columnas
        foreach ($columns->group as $group) {
            $groupItem = new ExtendedController\GroupItem();
            $groupItem->loadFromXML($group);
            $this->columns[$groupItem->name] = $groupItem;
            unset($groupItem);
        }
    }

    /**
     * Carga las condiciones especiales para las filas
     * desde el XML
     *
     * @param \SimpleXMLElement[] $rows
     */
    private function getXMLRows($rows)
    {
        if (empty($rows)) {
            return;
        }

        foreach ($rows->row as $row) {
            $rowItem = new ExtendedController\RowItem();
            $rowItem->loadFromXML($row);
            $this->rows[$rowItem->type] = $rowItem;
            unset($rowItem);
        }
    }

    /**
     * Añade a la configuración de un controlador
     *
     * @param string $name
     */
    public function installXML($name)
    {
        if ($this->name != $name) {
            $this->miniLog->critical($this->i18n->trans('error-install-name-xmlview'));
            return;
        }

        $file = "Core/XMLView/{$name}.xml";
        /**
         * This can be affected by a PHP bug #62577 (https://bugs.php.net/bug.php?id=62577)
         * Reports 'simplexml_load_file(...)' calls, which may be affected by this PHP bug.
         * $xml = simplexml_load_file($file);
         */
        $xml = @simplexml_load_string(file_get_contents($file));

        if ($xml === false) {
            $this->miniLog->critical($this->i18n->trans('error-processing-xmlview', [$file]));
            return;
        }

        $this->getXMLGroupsColumns($xml->columns);
        $this->getXMLRows($xml->rows);
    }

    /**
     * Obtiene la configuración para el controlador y usuario
     *
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

        // Load data from database, if not exist install xmlview
        if (!$this->loadFromCode('', $where, $orderby)) {
            $this->name = $name;
            $this->columns = [];
            $this->filters = [];
            $this->rows = [];
            $this->installXML($name);
            //$this->save();
        }

        // Aplicamos sobre los widgets Select dinámicos sus valores
        $this->dynamicSelectValues();
    }

    /**
     * Carga la lista de valores para un widget de tipo select dinámico
     * con un modelo de la base de datos o un rango de valores
     */
    private function dynamicSelectValues()
    {
        foreach ($this->columns as $group) {
            foreach ($group->columns as $column) {
                if ($column->widget->type === 'select') {
                    if (isset($column->widget->values[0]['source'])) {
                        $tableName = $column->widget->values[0]['source'];
                        $fieldCode = $column->widget->values[0]['fieldcode'];
                        $fieldDesc = $column->widget->values[0]['fieldtitle'];
                        $allowEmpty = !$column->widget->required;
                        $rows = CodeModel::all($tableName, $fieldCode, $fieldDesc, $allowEmpty);
                        $column->widget->setValuesFromCodeModel($rows);
                        unset($rows);
                    }

                    /// para los bucles como este <values start="0" end="5" step="1"></values>
                    if (isset($column->widget->values[0]['start'])) {
                        $start = $column->widget->values[0]['start'];
                        $end = $column->widget->values[0]['end'];
                        $step = $column->widget->values[0]['step'];
                        $values = range($start, $end, $step);
                        $column->widget->setValuesFromArray($values);
                    }
                }
            }
        }
    }
}
