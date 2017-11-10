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

namespace FacturaScripts\Core\Base\ExtendedController;

use FacturaScripts\Core\Base;
use Symfony\Component\HttpFoundation\Response;

/**
 * Definición de vista para uso en ListController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListView extends BaseView
{
    /**
     * Constantes para ordenación
     */
    const ICON_ASC = 'fa-sort-amount-asc';
    const ICON_DESC = 'fa-sort-amount-desc';

    /**
     * Cursor con los datos del modelo a mostrar
     *
     * @var array
     */
    private $cursor;

    /**
     * Configuración de filtros predefinidos por usuario
     *
     * @var array
     */
    private $filters;

    /**
     * Lista de campos donde buscar cuando se aplica una búsqueda
     *
     * @var array
     */
    private $searchIn;

    /**
     * Lista de campos disponibles en el order by
     * Ejemplo: orderby[key] = ["label" => "Etiqueta", "icon" => ICON_ASC]
     *          key = field_asc | field_desc
     * @var array
     */
    private $orderby;

    /**
     * Elemento seleccionado en el lista de order by
     * @var string
     */
    public $selectedOrderBy;

    /**
     * Almacena el offset para el cursor
     * @var int
     */
    private $offset;

    /**
     * Almacena el order para el cursor
     * @var array
     */
    private $order;

    /**
     * Almacena los parámetros del where del cursor
     * @var array
     */
    private $where;

    /**
     * Constructor e inicializador de la clase
     *
     * @param string $title
     * @param string $modelName
     * @param string $viewName
     * @param string $userNick
     */
    public function __construct($title, $modelName, $viewName, $userNick)
    {
        parent::__construct($title, $modelName);

        $this->cursor = [];
        $this->orderby = [];
        $this->filters = [];
        $this->searchIn = [];
        $this->count = 0;
        $this->selectedOrderBy = '';

        // Carga configuración de la vista para el usuario
        $this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Devuelve el texto de un enlace para un modelo dado.
     *
     * @param $data
     *
     * @return string
     */
    public function getClickEvent($data)
    {
        foreach ($this->getColumns() as $col) {
            if (isset($col->widget->onClick)) {
                return '?page=' . $col->widget->onClick . '&code=' . $data->{$col->widget->fieldName};
            }
        }

        return '';
    }

    /**
     * Devuelve la lista de datos leidos en formato Model
     *
     * @return array
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Devuelve la lista de filtros definidos
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Devuelve la lista de campos para la búsqueda en formato para WhereDatabase
     *
     * @return string
     */
    public function getSearchIn()
    {
        return implode('|', $this->searchIn);
    }

    /**
     * Devuelve la lista de order by definidos
     *
     * @return array
     */
    public function getOrderBy()
    {
        return $this->orderby;
    }

    /**
     * Lista de columnas y su configuración
     * (Array of ColumnItem)
     * @return array
     */
    public function getColumns()
    {
        $key = array_keys($this->pageOption->columns)[0];
        return $this->pageOption->columns[$key]->columns;
    }

    /**
     * Devuelve el Order By indicado en formato array
     *
     * @param string $orderKey
     * @return array
     */
    public function getSQLOrderBy($orderKey = '')
    {
        if (empty($this->orderby)) {
            return [];
        }

        if ($orderKey === '') {
            $orderKey = array_keys($this->orderby)[0];
        }

        $orderby = explode('_', $orderKey);
        return [$orderby[0] => $orderby[1]];
    }

    /**
     * Comprueba y establece el valor seleccionado en el order by
     *
     * @param string $orderKey
     */
    public function setSelectedOrderBy($orderKey)
    {
        $keys = array_keys($this->orderby);
        if (empty($orderKey) || !in_array($orderKey, $keys)) {
            if (empty($this->selectedOrderBy)) {
                $this->selectedOrderBy = (string) $keys[0]; // We force the first element when there is no default
            }
        } else {
            $this->selectedOrderBy = $orderKey;
        }
    }

    /**
     * Añade a la lista de campos para la búsqueda los campos informados
     *
     * @param array $fields
     */
    public function addSearchIn($fields)
    {
        if (is_array($fields)) {
            $this->searchIn += $fields;
        }
    }

    /**
     * Añade un campo a la lista de Order By
     *
     * @param string $field
     * @param string $label
     * @param int $default    (0 = None, 1 = ASC, 2 = DESC)
     */
    public function addOrderBy($field, $label = '', $default = 0)
    {
        $key1 = strtolower($field) . '_asc';
        $key2 = strtolower($field) . '_desc';
        if (empty($label)) {
            $label = $field;
        }

        $this->orderby[$key1] = ['icon' => self::ICON_ASC, 'label' => static::$i18n->trans($label)];
        $this->orderby[$key2] = ['icon' => self::ICON_DESC, 'label' => static::$i18n->trans($label)];

        switch ($default) {
            case 1:
                $this->setSelectedOrderBy($key1);
                break;

            case 2:
                $this->setSelectedOrderBy($key2);
                break;

            default:
                break;
        }
    }

    /**
     * Define una nueva opción de filtrado para los datos
     *
     * @param string $key
     * @param ListFilter $filter
     */
    public function addFilter($key, $filter)
    {
        if (empty($filter->options['field'])) {
            $filter->options['field'] = $key;
        }

        if (isset($filter->options['label'])) {
            $filter->options['label'] = static::$i18n->trans($filter->options['label']);
        }

        $this->filters[$key] = $filter;
    }

    /**
     * Establece el estado de visualización de una columna
     * 
     * @param string $columnName
     * @param boolean $disabled
     */
    public function disableColumn($columnName, $disabled)
    {
        $column = $this->columnForName($columnName);
        if (!empty($column)) {
            $column->display = $disabled ? 'none' : 'left';
        }
    }

    /**
     * Carga los datos
     *
     * @param array $where
     * @param int $offset
     * @param int $limit
     */
    public function loadData($where, $offset = 0, $limit = 50)
    {
        $order = $this->getSQLOrderBy($this->selectedOrderBy);
        $this->count = $this->model->count($where);
        if ($this->count > 0) {
            $this->cursor = $this->model->all($where, $order, $offset, $limit);
        }

        /// nos guardamos los valores where y offset para la exportación
        $this->offset = $offset;
        $this->order = $order;
        $this->where = $where;
    }

    /**
     * Método para la exportación de los datos de la vista
     *
     * @param Base\ExportManager $exportManager
     * @param Response $response
     * @param string $action
     *
     * @return mixed
     */
    public function export(&$exportManager, &$response, $action)
    {
        return $exportManager->generateList($response, $action, $this->model, $this->where, $this->order, $this->offset, $this->getColumns());
    }
}
