<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\API\Base\APIResourceClass;
use FacturaScripts\Core\Model\Base\ModelClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIModel is the class for any API Model Resource in Dinamic/Model folder.
 *
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 * @author Rafael San José Tovar <rsanjoseo@gmail.com>
 */
class APIModel extends APIResourceClass
{

    /**
     * ModelClass object.
     *
     * @var ModelClass $model
     */
    private $model;

    /**
     * Process the GET request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doDELETE(): bool
    {
        if (empty($this->params) || false === $this->model->loadFromCode($this->params[0])) {
            $this->setError($this->toolBox()->i18n()->trans('record-not-found'));
            return false;
        }

        if ($this->model->delete()) {
            $this->setOk($this->toolBox()->i18n()->trans('record-deleted-correctly'), $this->model->toArray());
            return true;
        }

        $this->setError($this->toolBox()->i18n()->trans('record-deleted-error'));
        return false;
    }

    /**
     * Process the GET request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doGET(): bool
    {
        /// all records
        if (empty($this->params)) {
            return $this->listAll();
        }

        /// model schema
        if ($this->params[0] === 'schema') {
            $data = [];
            foreach ($this->model->getModelFields() as $key => $value) {
                $data[$key] = [
                    'type' => $value['type'],
                    'default' => $value['default'],
                    'is_nullable' => $value['is_nullable']
                ];
            }
            $this->returnResult($data);
            return true;
        }

        /// record not found
        if (!$this->model->loadFromCode($this->params[0])) {
            $this->setError($this->toolBox()->i18n()->trans('record-not-found'));
            return false;
        }

        $this->returnResult($this->model->toArray());
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
        if ($this->model->loadFromCode($code)) {
            $this->setError($this->toolBox()->i18n()->trans('duplicate-record'), $this->model->toArray());
            return false;
        }

        /// TODO: Why don't use $this->modal->loadFromData() ???
        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
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
        if (!$this->model->loadFromCode($code)) {
            $this->setError($this->toolBox()->i18n()->trans('record-not-found'));
            return false;
        }

        /// TODO: Why don't use $this->modal->loadFromData() ???
        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
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
     * This method is equivalent to $this->request->get($key, $default),
     * but always return an array, as expected for some parameters like operation, filter or sort.
     *
     * @param string $key
     * @param string $default
     *
     * @return array
     */
    private function getRequestArray($key, $default = ''): array
    {
        $array = $this->request->get($key, $default);
        return \is_array($array) ? $array : []; /// if is string has bad format
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
        foreach (\scandir(\FS_FOLDER . '/Dinamic/' . $folder, \SCANDIR_SORT_ASCENDING) as $fName) {
            if (\substr($fName, -4) === '.php') {
                $modelName = substr($fName, 0, -4);
                $plural = $this->pluralize($modelName);
                $resources[$plural] = $this->setResource($modelName);
            }
        }

        return $resources;
    }

    /**
     * Returns the where clauses.
     *
     * @param array  $filter
     * @param array  $operation
     * @param string $defaultOperation
     *
     * @return DataBaseWhere[]
     */
    private function getWhereValues($filter, $operation, $defaultOperation = 'AND'): array
    {
        $where = [];
        foreach ($filter as $key => $value) {
            $field = $key;
            $operator = '=';

            switch (\substr($key, -3)) {
                case '_gt':
                    $field = \substr($key, 0, -3);
                    $operator = '>';
                    break;

                case '_is':
                    $field = \substr($key, 0, -3);
                    $operator = 'IS';
                    break;

                case '_lt':
                    $field = \substr($key, 0, -3);
                    $operator = '<';
                    break;
            }

            switch (\substr($key, -4)) {
                case '_gte':
                    $field = \substr($key, 0, -4);
                    $operator = '>=';
                    break;

                case '_lte':
                    $field = \substr($key, 0, -4);
                    $operator = '<=';
                    break;

                case '_neq':
                    $field = \substr($key, 0, -4);
                    $operator = '!=';
                    break;
            }

            if (\substr($key, -5) == '_like') {
                $field = \substr($key, 0, -5);
                $operator = 'LIKE';
            } elseif (\substr($key, -6) == '_isnot') {
                $field = \substr($key, 0, -6);
                $operator = 'IS NOT';
            }

            if (!isset($operation[$key])) {
                $operation[$key] = $defaultOperation;
            }

            $where[] = new DataBaseWhere($field, $value, $operator, $operation[$key]);
        }

        return $where;
    }

    /**
     *
     * @return bool
     */
    protected function listAll(): bool
    {
        $filter = $this->getRequestArray('filter');
        $limit = (int) $this->request->get('limit', 50);
        $offset = (int) $this->request->get('offset', 0);
        $operation = $this->getRequestArray('operation');
        $order = $this->getRequestArray('sort');

        $where = $this->getWhereValues($filter, $operation);
        $data = $this->model->all($where, $order, $offset, $limit);
        $this->returnResult($data);
        return true;
    }

    /**
     * Convert $text to plural
     *
     * TODO: The conversion to the plural is language dependent.
     *
     * @param $text
     *
     * @return string
     */
    private function pluralize($text): string
    {
        if (\substr($text, -1) === 's') {
            return \strtolower($text);
        }

        if (\substr($text, -3) === 'ser' || \substr($text, -4) === 'tion') {
            return \strtolower($text) . 's';
        }

        if (\in_array(\substr($text, -1), ['a', 'e', 'i', 'o', 'u', 'k'], false)) {
            return \strtolower($text) . 's';
        }

        return \strtolower($text) . 'es';
    }

    /**
     *
     * @return bool
     */
    private function saveResource(): bool
    {
        if ($this->model->save()) {
            $this->setOk($this->toolBox()->i18n()->trans('record-updated-correctly'), $this->model->toArray());
            return true;
        }

        $message = $this->toolBox()->i18n()->trans('record-save-error');
        foreach ($this->toolBox()->log()->readAll() as $log) {
            $message .= ' - ' . $log['message'];
        }

        $this->setError($message, $this->model->toArray());
        return false;
    }
}
