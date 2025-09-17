<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AttachedFile;

class ApiAttachedFiles extends ApiController
{
    /** @var AttachedFile */
    private $model;

    protected function runResource(): void
    {
        $this->model = new AttachedFile();

        try {
            switch ($this->request->method()) {
                case 'DELETE':
                    $this->doDELETE();
                    return;

                case 'GET':
                    $this->doGET();
                    return;

                case 'PATCH':
                case 'PUT':
                    $this->doPUT();
                    return;

                case 'POST':
                    $this->doPOST();
                    return;
            }
        } catch (Exception $exc) {
            $this->setError('API-ERROR: ' . $exc->getMessage(), null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->response
            ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
            ->json([
                'status' => 'error',
                'message' => 'method-not-allowed',
            ]);
    }

    public function doDELETE(): void
    {
        if (empty($this->getUriParam(3)) || false === $this->model->load($this->getUriParam(3))) {
            $this->setError(Tools::trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return;
        }

        if (false === $this->model->delete()) {
            $this->setError(Tools::trans('record-deleted-error'), null, Response::HTTP_CONFLICT);
            return;
        }

        $this->setOk(Tools::trans('record-deleted-correctly'), $this->model->toArray());
    }

    public function doGET(): void
    {
        // all records
        if (empty($this->getUriParam(3))) {
            $this->listAll();
            return;
        }

        // model schema
        if ($this->getUriParam(3) === 'schema') {
            $data = [];
            foreach ($this->model->getModelFields() as $key => $value) {
                $data[$key] = [
                    'type' => $value['type'],
                    'default' => $value['default'],
                    'is_nullable' => $value['is_nullable']
                ];
            }
            $this->returnResult($data);
            return;
        }

        // record not found
        if (false === $this->model->load($this->getUriParam(3))) {
            $this->setError(Tools::trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return;
        }

        $data = $this->model->toArray();
        $data['download'] = $this->model->url('download');
        $data['download-permanent'] = $this->model->url('download-permanent');
        $this->returnResult($data);
    }

    public function doPOST(): void
    {
        $field = $this->model->primaryColumn();
        $values = $this->request->request->all();
        $files = $this->request->files->all();

        $param0 = empty($this->getUriParam(3)) ? '' : $this->getUriParam(3);
        $code = $values[$field] ?? $param0;
        if ($this->model->load($code)) {
            $this->setError(Tools::trans('duplicate-record'), $this->model->toArray(), Response::HTTP_CONFLICT);
            return;
        } elseif (empty($values) && empty($files)) {
            $this->setError(Tools::trans('no-data-received-form'));
            return;
        }

        // recorremos los archivos recibidos
        foreach ($this->request->files->all() as $file) {
            if (!$file->isValid()) {
                continue;
            }
            // si el archivo es php saltamos
            if ($file->extension() === 'php') {
                continue;
            }
            $file->move('MyFiles', $file->getClientOriginalName());
            $this->model->path = $file->getClientOriginalName();
        }
        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
        }

        $this->saveResource();
    }

    public function doPUT(): void
    {
        $field = $this->model->primaryColumn();
        $values = $this->request->request->all();

        $param0 = empty($this->getUriParam(3)) ? '' : $this->getUriParam(3);
        $code = $values[$field] ?? $param0;
        if (false === $this->model->load($code)) {
            $this->setError(Tools::trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return;
        } elseif (empty($values)) {
            $this->setError(Tools::trans('no-data-received-form'));
            return;
        }

        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
        }

        $this->saveResource();
    }

    private function getWhereValues($filter, $operation, $defaultOperation = 'AND'): array
    {
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

            $where[] = new DataBaseWhere($field, $value, $operator, $operation[$key]);
        }

        return $where;
    }

    protected function listAll(): void
    {
        $filter = $this->request->query->getArray('filter');
        $limit = $this->request->query->getInt('limit', 50);
        $offset = $this->request->query->getInt('offset', 0);
        $operation = $this->request->query->getArray('operation');
        $order = $this->request->query->getArray('sort');

        // obtenemos los registros
        $where = $this->getWhereValues($filter, $operation);
        $data = [];
        foreach ($this->model->all($where, $order, $offset, $limit) as $item) {
            $raw = $item->toArray();
            $raw['download'] = $item->url('download');
            $raw['download-permanent'] = $item->url('download-permanent');
            $data[] = $raw;
        }

        // obtenemos el count y lo ponemos en el header
        $count = $this->model->count($where);
        $this->response->header('X-Total-Count', $count);

        $this->returnResult($data);
    }

    protected function returnResult(array $data): void
    {
        $this->response
            ->setHttpCode(Response::HTTP_OK)
            ->json($data);
    }

    private function saveResource(): void
    {
        if ($this->model->save()) {
            $this->setOk(Tools::trans('record-updated-correctly'), $this->model->toArray());
            return;
        }

        $message = Tools::trans('record-save-error');
        foreach (Tools::log()->read('', ['critical', 'error', 'info', 'notice', 'warning']) as $log) {
            $message .= ' - ' . $log['message'];
        }

        $this->setError($message, $this->model->toArray(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function setError(string $message, ?array $data = null, int $status = Response::HTTP_BAD_REQUEST): void
    {
        Tools::log('api')->error($message);

        $res = ['error' => $message];
        if ($data !== null) {
            $res['data'] = $data;
        }

        $this->response
            ->setHttpCode($status)
            ->json($res);
    }

    protected function setOk(string $message, ?array $data = null): void
    {
        Tools::log('api')->notice($message);

        $res = ['ok' => $message];
        if ($data !== null) {
            $res['data'] = $data;
        }

        $this->response
            ->setHttpCode(Response::HTTP_OK)
            ->json($res);
    }
}
