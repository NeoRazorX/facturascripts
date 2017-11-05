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

use FacturaScripts\Core\Model;
use FacturaScripts\Core\Base;
use Symfony\Component\HttpFoundation\Response;

/**
 * Definición base para vistas de uso en ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class BaseView
{
    /**
     * Modelo necesario para llamadas a los métodos del modelo
     * o en el caso del EditController contiene los datos visualizados.
     *
     * @var mixed
     */
    protected $model;

    /**
     * Configuración de columnas y filtros
     *
     * @var Model\PageOption
     */
    protected $pageOption;

    /**
     * Título identificativo de la vista
     *
     * @var string
     */
    public $title;

    /**
     * Número total de registros leídos
     * @var int
     */
    public $count;

    /**
     * Contiene el traductor
     *
     * @var Base\Translator
     */
    public static $i18n;

    /**
     * Establece el estado de visualización/edición de una columna
     *
     * @param string $columnName
     * @param boolean $disabled
     */
    abstract public function disableColumn($columnName, $disabled);

    /**
     * Método para la exportación de los datos de la vista
     *
     * @param Base\ExportManager $exportManager
     * @param Response $response
     * @param string $action
     */
    abstract public function export(&$exportManager, &$response, $action);

    /**
     * Constructor e inicializador de la clase
     *
     * @param string $title
     * @param string $modelName
     */
    public function __construct($title, $modelName)
    {
        static::$i18n = new Base\Translator();

        $this->count = 0;
        $this->title = static::$i18n->trans($title);
        $this->model = empty($modelName) ? null : new $modelName;
        $this->pageOption = new Model\PageOption();
    }

    /**
     * Verifica la estructura y carga en el modelo los datos informados en un array
     *
     * @param array $data
     */
    public function loadFromData(&$data)
    {
        $fieldKey = $this->model->primaryColumn();
        $fieldValue = $data[$fieldKey];
        if ($fieldValue != $this->model->primaryColumnValue()) {
            $this->model->loadFromCode($fieldValue);
        }

        $this->model->checkArrayData($data);
        $this->model->loadFromData($data, ['action', 'active']);
    }

    /**
     * Persiste los datos del modelo en la base de datos
     *
     * @return boolean
     */
    public function save()
    {
        return $this->model->save();
    }

    /**
     * Elimina el registro con el código indicado de la base de datos
     *
     * @param string $code
     * @return boolean
     */
    public function delete($code)
    {
        if ($this->model->loadFromCode($code)) {
            return $this->model->delete();
        }

        return false;
    }

    /**
     * Devuelve el puntero al modelo de datos
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Obtiene la columna por el nombre de la columna
     *
     * @param string $columnName
     *
     * @return ExtendedController\ColumnItem
     */
    public function columnForName($columnName)
    {
        $result = null;
        foreach ($this->pageOption->columns as $group) {
            foreach ($group->columns as $key => $column) {
                if ($key === $columnName) {
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

    /**
     * Obtiene la columna para el nombre de campo informado
     *
     * @param string $fieldName
     *
     * @return ExtendedController\ColumnItem
     */
    public function columnForField($fieldName)
    {
        $result = null;
        foreach ($this->pageOption->columns as $group) {
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

    /**
     * Si existe, devuelve el tipo de row especificado
     *
     * @param string $key
     *
     * @return RowItem
     */
    public function getRow($key)
    {
        return empty($this->pageOption->rows) ? null : $this->pageOption->rows[$key];
    }

    /**
     * Devuelve la url del modelo del tipo solicitado
     *
     * @param string $type      (edit / list / auto)
     * @return string
     */
    public function getURL($type)
    {
        return empty($this->model) ? '' : $this->model->url($type);
    }

    /**
     * Devuelve el identificador del modelo
     *
     * @return string
     */
    public function getModelID()
    {
        return empty($this->model) ? '' : $this->model->modelClassName();
    }
}
