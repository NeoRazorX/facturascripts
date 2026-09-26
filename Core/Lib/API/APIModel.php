<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\API;

use Exception;
use FacturaScripts\Core\Lib\API\Base\APIResourceClass;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Recurso genérico de la API que expone automáticamente todos los modelos de la
 * carpeta Dinamic/Model, publicando cada uno con su nombre en plural y admitiendo
 * las operaciones GET (listado, ficha y esquema), POST, PUT/PATCH y DELETE.
 * Traduce los parámetros filter, operation, sort, limit y offset de la petición a
 * cláusulas Where del modelo, validando que las columnas existan y no estén ocultas.
 * Los modelos que no deban publicarse se descartan con excludeModel(), que los plugins
 * pueden invocar desde su Init::init().
 *
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 * @author Rafael San José Tovar <rsanjoseo@gmail.com>
 */
class APIModel extends APIResourceClass
{
    /** @var string[] */
    private static $excluded_models = ['CodeModel', 'TotalModel'];

    /**
     * ModelClass object.
     *
     * @var ModelClass $model
     */
    private $model;

    /**
     * Excludes a model from the API resources map.
     *
     * Plugins can call this method from Init::init(), using the class name
     * without namespace.
     *
     * @param string $modelName
     */
    public static function excludeModel(string $modelName): void
    {
        if (false === in_array($modelName, self::$excluded_models, true)) {
            self::$excluded_models[] = $modelName;
        }
    }

    /**
     * Process the GET request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doDELETE(): bool
    {
        if (empty($this->params) || false === $this->model->load($this->params[0])) {
            $this->setError(Tools::trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return false;
        }

        if ($this->model->delete()) {
            $hidden = $this->model->getApiFieldsToHide();
            $this->setOk(Tools::trans('record-deleted-correctly'), $this->filterHidden($this->model->toArray(), $hidden));
            return true;
        }

        $this->setError(Tools::trans('record-deleted-error'));
        return false;
    }

    /**
     * Process the GET request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doGET(): bool
    {
        // all records
        if (empty($this->params)) {
            return $this->listAll();
        }

        $hidden = $this->model->getApiFieldsToHide();

        // model schema
        if ($this->params[0] === 'schema') {
            $data = [];
            foreach ($this->model->getModelFields() as $key => $value) {
                if (in_array($key, $hidden, true)) {
                    continue;
                }
                $data[$key] = [
                    'type' => $value['type'],
                    'default' => $value['default'],
                    'is_nullable' => $value['is_nullable']
                ];
            }
            $this->returnResult($data);
            return true;
        }

        // record not found
        if (false === $this->model->load($this->params[0])) {
            $this->setError(Tools::trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return false;
        }

        $this->returnResult($this->filterHidden($this->model->toArray(true), $hidden));
        return true;
    }

    /**
     * Process the POST (create) request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doPOST(): bool
    {
        $field = $this->model->primaryColumn();
        $values = $this->request->request->all();

        $param0 = empty($this->params) ? '' : $this->params[0];
        $code = $values[$field] ?? $param0;
        if ($this->model->load($code)) {
            $hidden = $this->model->getApiFieldsToHide();
            $this->setError(Tools::trans('duplicate-record'), $this->filterHidden($this->model->toArray(), $hidden));
            return false;
        } elseif (empty($values)) {
            $this->setError(Tools::trans('no-data-received-form'));
            return false;
        }

        foreach ($values as $key => $value) {
            $this->model->{$key} = $value === 'null' ? null : $value;
        }

        return $this->saveResource();
    }

    /**
     * Process the PUT (update) request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doPUT(): bool
    {
        $field = $this->model->primaryColumn();
        $values = $this->request->request->all();

        $param0 = empty($this->params) ? '' : $this->params[0];
        $code = $values[$field] ?? $param0;
        if (false === $this->model->load($code)) {
            $this->setError(Tools::trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return false;
        } elseif (empty($values)) {
            $this->setError(Tools::trans('no-data-received-form'));
            return false;
        }

        foreach ($values as $key => $value) {
            $this->model->{$key} = $value === 'null' ? null : $value;
        }

        return $this->saveResource();
    }

    /**
     * Returns an associative array with the resources, where the index is
     * the public name of the resource.
     *
     * @return array
     */
    public function getResources(): array
    {
        return $this->getResourcesFromFolder('Model');
    }

    /**
     * Process the model resource, allowing POST/PUT/DELETE/GET ALL actions
     *
     * @param string $name
     *
     * @return bool
     */
    public function processResource(string $name): bool
    {
        try {
            $modelName = 'FacturaScripts\\Dinamic\\Model\\' . $name;
            $this->model = new $modelName();

            return parent::processResource($name);
        } catch (Exception $exc) {
            $this->setError('API-ERROR: ' . $exc->getMessage(), null, Response::HTTP_INTERNAL_SERVER_ERROR);
            return false;
        }
    }

    /**
     * Load resource map from a folder
     *
     * @param string $folder
     *
     * @return array
     */
    private function getResourcesFromFolder($folder): array
    {
        $resources = [];
        foreach (scandir(FS_FOLDER . '/Dinamic/' . $folder, SCANDIR_SORT_ASCENDING) as $fName) {
            if (substr($fName, -4) === '.php') {
                $modelName = substr($fName, 0, -4);

                if (in_array($modelName, self::$excluded_models, true)) {
                    continue;
                }

                $plural = $this->pluralize($modelName);
                $resources[$plural] = $this->setResource($modelName);
            }
        }

        return $resources;
    }

    /**
     * Returns the where clauses.
     *
     * @param array $filter
     * @param array $operation
     * @param string $defaultOperation
     *
     * @return Where[]
     */
    private function getWhereValues($filter, $operation, $defaultOperation = 'AND', array &$badFields = []): array
    {
        $allowedFields = array_keys($this->model->getModelFields());
        $hidden = $this->model->getApiFieldsToHide();

        $where = [];
        foreach ($filter as $key => $value) {
            $field = $key;
            $operator = '=';

            switch (substr($key, -3)) {
                case '_gt':
                    $field = substr($key, 0, -3);
                    $operator = '>';
                    break;

                case '_is':
                    $field = substr($key, 0, -3);
                    $operator = 'IS';
                    break;

                case '_lt':
                    $field = substr($key, 0, -3);
                    $operator = '<';
                    break;
            }

            switch (substr($key, -4)) {
                case '_gte':
                    $field = substr($key, 0, -4);
                    $operator = '>=';
                    break;

                case '_lte':
                    $field = substr($key, 0, -4);
                    $operator = '<=';
                    break;

                case '_neq':
                    $field = substr($key, 0, -4);
                    $operator = '!=';
                    break;
            }

            if (substr($key, -5) == '_null') {
                $field = substr($key, 0, -5);
                $operator = 'IS';
                $value = null;
            } elseif (substr($key, -8) == '_notnull') {
                $field = substr($key, 0, -8);
                $operator = 'IS NOT';
                $value = null;
            }

            if (substr($key, -5) == '_like') {
                $field = substr($key, 0, -5);
                $operator = 'LIKE';
            } elseif (substr($key, -6) == '_isnot') {
                $field = substr($key, 0, -6);
                $operator = 'IS NOT';
            }

            if (!isset($operation[$key])) {
                $operation[$key] = $defaultOperation;
            }

            // solo aceptamos identificadores simples (columna o tabla.columna)
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $field)) {
                $badFields[] = $field;
                continue;
            }

            // la columna debe existir en el modelo y no estar oculta
            $column = strpos($field, '.') === false ? $field : substr($field, strpos($field, '.') + 1);
            if (!in_array($column, $allowedFields, true) || in_array($column, $hidden, true)) {
                $badFields[] = $field;
                continue;
            }

            $where[] = new Where($field, $value, $operator, $operation[$key]);
        }

        return $where;
    }

    protected function listAll(): bool
    {
        $filter = $this->request->query->getArray('filter');
        $limit = $this->request->query->getInt('limit', 50);
        $offset = $this->request->query->getInt('offset', 0);
        $operation = $this->request->query->getArray('operation');
        $order = $this->request->query->getArray('sort');

        // obtenemos los registros
        $data = [];
        $hidden = $this->model->getApiFieldsToHide();
        $badFields = [];
        $order = $this->filterOrder($order, $hidden, $badFields);
        $where = $this->getWhereValues($filter, $operation, 'AND', $badFields);
        if (!empty($badFields)) {
            $this->setError('api: fields not allowed: ' . implode(', ', array_unique($badFields)));
            return false;
        }

        // sin orden, el limit/offset no es estable: ordenamos por la clave primaria
        if (empty($order)) {
            $order = [$this->model->primaryColumn() => 'ASC'];
        }

        foreach ($this->model->all($where, $order, $offset, $limit) as $item) {
            $data[] = $this->filterHidden($item->toArray(true), $hidden);
        }

        // obtenemos el count y lo ponemos en el header
        $count = $this->model->count($where);
        $this->response->header('X-Total-Count', $count);

        $this->returnResult($data);
        return true;
    }

    /**
     * Convert $text to plural
     *
     * @param $text
     *
     * @return string
     */
    private function pluralize($text): string
    {
        if (substr($text, -1) === 's') {
            return strtolower($text);
        }

        if (substr($text, -3) === 'ser' || substr($text, -4) === 'tion') {
            return strtolower($text) . 's';
        }

        if (in_array(substr($text, -1), ['a', 'e', 'i', 'o', 'u', 'k'], false)) {
            return strtolower($text) . 's';
        }

        return strtolower($text) . 'es';
    }

    private function saveResource(): bool
    {
        $hidden = $this->model->getApiFieldsToHide();

        if ($this->model->save()) {
            $this->setOk(Tools::trans('record-updated-correctly'), $this->filterHidden($this->model->toArray(true), $hidden));
            return true;
        }

        $message = Tools::trans('record-save-error');
        foreach (Tools::log()->read('', ['critical', 'error', 'info', 'notice', 'warning']) as $log) {
            $message .= ' - ' . $log['message'];
        }

        $this->setError($message, $this->filterHidden($this->model->toArray(true), $hidden));
        return false;
    }

    private function filterOrder(array $order, array $hidden, array &$badFields = []): array
    {
        $allowedFields = array_keys($this->model->getModelFields());
        $result = [];
        foreach ($order as $key => $value) {
            // admitimos prefijos integer:, lower:, upper:
            $field = $key;
            foreach (['integer:', 'lower:', 'upper:'] as $prefix) {
                if (str_starts_with($field, $prefix)) {
                    $field = substr($field, strlen($prefix));
                    break;
                }
            }

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $field)) {
                $badFields[] = $key;
                continue;
            }

            $column = strpos($field, '.') === false ? $field : substr($field, strpos($field, '.') + 1);
            if (!in_array($column, $allowedFields, true) || in_array($column, $hidden, true)) {
                $badFields[] = $key;
                continue;
            }

            $result[$key] = $value;
        }
        return $result;
    }

    private function filterHidden(array $data, array $hidden): array
    {
        foreach ($hidden as $field) {
            // sin punto, es una columna: la eliminamos
            $pos = strpos($field, '.');
            if ($pos === false) {
                unset($data[$field]);
                continue;
            }

            // con punto, es una clave dentro de una columna json: columna.clave
            $column = substr($field, 0, $pos);
            $key = substr($field, $pos + 1);
            if (!isset($data[$column]) || !is_string($data[$column])) {
                continue;
            }

            $json = json_decode($data[$column], true);
            if (is_array($json) && array_key_exists($key, $json)) {
                unset($json[$key]);
                $data[$column] = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $data;
    }
}
