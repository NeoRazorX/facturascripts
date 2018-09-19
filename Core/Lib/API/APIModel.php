<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\API;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\API\Base\APIResourceClass;
use FacturaScripts\Core\Model\Base\ModelClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIModel is the class for any API Model Resource in Dinamic/Model folder.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Rafael San José Tovar (http://www.x-netdigital.com) <rsanjoseo@gmail.com>
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
     * Convert $text to plural
     *
     * TODO: The conversion to the plural is language dependent.
     *
     * @param $text
     * @return string
     */
    private function pluralize($text): string
    {
        /// Conversion to plural
        if (substr($text, -1) === 's') {
            return strtolower($text);
        }
        if (substr($text, -3) === 'ser' || substr($text, -4) === 'tion') {
            return strtolower($text) . 's';
        }
        if (\in_array(substr($text, -1), ['a', 'e', 'i', 'o', 'u', 'k'], false)) {
            return strtolower($text) . 's';
        }
        return strtolower($text) . 'es';
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
                $plural = $this->pluralize($modelName);
                $resources[$plural] = $this->setResource($modelName);
            }
        }
        return $resources;
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
     * Returns the where clauses.
     *
     * @param array $filter
     * @param array $operation
     * @param string $defaultOperation
     *
     * @return DataBaseWhere[]
     */
    private function getWhereValues($filter, $operation, $defaultOperation = 'AND'): array
    {
        $where = [];
        foreach ($filter as $key => $value) {
            if (!isset($operation[$key])) {
                $operation[$key] = $defaultOperation;
            }
            $where[] = new DataBaseWhere($key, $value, 'LIKE', $operation[$key]);
        }
        return $where;
    }

    protected function listAll(): bool
    {
        if ($this->method === 'GET') {
            $offset = (int) $this->request->get('offset', 0);
            $limit = (int) $this->request->get('limit', 50);
            $operation = $this->getRequestArray('operation');
            $filter = $this->getRequestArray('filter');
            $order = $this->getRequestArray('sort');
            $where = $this->getWhereValues($filter, $operation);

            $data = $this->model->all($where, $order, $offset, $limit);

            $this->returnResult($data);
            return true;
        }

        $this->setError('List all only in GET method');
        return false;
    }

    /**
     * Process the GET request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doGET(): bool
    {
        if ($this->params[0] === 'schema') {
            $data = [];
            foreach ($this->model->getModelFields() as $key => $value) {
                $data[$key] = [
                    'type' => $value['type'],
                    'default' => $value['default'],
                    'is_nullable' => $value['is_nullable'],
                ];
            }
            $this->returnResult($data);
            return true;
        }

        $data = (array) $this->model->get($this->params[0]);
        if (isset($data)) {
            // Return "array(1) { [0]=> bool(false) }" if not found???
            if (count($data) > 1 || !isset($data[0])) {
                $this->returnResult($data);
                return true;
            }
            $this->setError($this->params[0] . ' not found');
            return false;
        }
        $this->setError('Error getting data');
        return false;
    }

    /**
     * Load the model and replace the past data in the loaded model.
     * Returns true if the record already exists in the model, and false if not.
     * If the id is passed as data and parameter, verify that both match, 
     * returning error if there is inconsistency.
     * 
     * @return bool true if the resource exists in the model
     */
    private function getResource(): bool
    {
        $cod = $this->model->primaryColumn();

        // If editing, retrieve the current data
        $exist = $this->model->loadFromCode($this->params[0]);
        $this->model->{$cod} = $this->params[0];

        // Retrieve the past data, and replace the changes
        $values = $this->request->request->all();
        $values[$cod] = $this->params[0];
        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
        }

        return $exist;
    }

    private function saveResource(): bool
    {
        $this->fixTypes();
        if ($this->model->save()) {
            $this->setOk('data-saved', (array) $this->model);
            return true;
        }

        foreach ($this->miniLog->read() as $message) {
            $this->params[] = $message['message'];
        }

        $this->setError('bad-request', $this->request->request->all());
        return false;
    }

    /**
     * Process the POST (create) request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doPOST(): bool
    {
        if ($this->getResource()) {
            $this->setError('existing-record', (array) $this->model);
            return false;
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
        if (!$this->getResource()) {
            $this->setError('not-existing-record', (array) $this->model);
            return false;
        }

        return $this->saveResource();
    }

    /**
     * Process the GET request. Overwrite this function to implement is functionality.
     *
     * @return bool
     */
    public function doDELETE(): bool
    {
        if ($this->model->loadFromCode($this->params[0])) {
            $data = (array) $this->model;
            $this->model->delete();
            $this->setOk('record-deleted', $data);
            return true;
        }

        $this->setError($this->params[0] . ' not found');
        return false;
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

            if (count($this->params) === 0) {
                $this->method = $this->request->getMethod();
                return $this->listAll();
            }

            return parent::processResource($name);
        } catch (\Exception $ex) {
            $this->setError('api-error', null, Response::HTTP_INTERNAL_SERVER_ERROR);
            return false;
        }
    }

    /**
     * API receive all data as string, with this we convert to correct data types.
     *
     * @return void
     */
    private function fixTypes()
    {
        foreach ($this->model->getModelFields() as $key => $value) {
            $fieldType = $value['type'];
            // Force to match type from supported types in XML table definitions
            if (\is_bool($this->model->{$key})) {
                if (\in_array($fieldType, ['boolean', 'tinyint(1)'])) {
                    $this->model->{$key} = (bool) $this->model->{$key};
                    continue;
                }
            }
            if (\is_numeric($this->model->{$key})) {
                if (\strpos($fieldType, 'double') === 0) {
                    $this->model->{$key} = (double) $this->model->{$key};
                    continue;
                }
                $this->model->{$key} = (int) $this->model->{$key};
            }
        }
    }
}
