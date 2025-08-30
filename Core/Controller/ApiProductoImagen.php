<?php

namespace FacturaScripts\Core\Controller;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ProductoImagen;

class ApiProductoImagen extends ApiController
{
    protected function runResource(): void
    {
        $this->model = new ProductoImagen();
        try {
            switch ($this->request->method()) {
                case 'DELETE':
                    $this->doDELETE();
                    break;

                case 'GET':
                    $this->doGET();
                    break;

                case 'PATCH':
                case 'PUT':
                    $this->doPUT();
                    break;

                case 'POST':
                    $this->doPOST();
                    break;
            }
        } catch (Exception $exc) {
            $this->setError('API-ERROR: ' . $exc->getMessage(), null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function doDELETE(): bool
    {
        if (empty($this->getUriParam(3)) || false === $this->model->loadFromCode($this->getUriParam(3))) {
            $this->setError(Tools::lang()->trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return false;
        }

        if ($this->model->delete()) {
            $this->setOk(Tools::lang()->trans('record-deleted-correctly'), $this->model->toArray());
            return true;
        }

        $this->setError(Tools::lang()->trans('record-deleted-error'));
        return false;
    }

    public function doGET(): bool
    {
        // all records
        if (empty($this->getUriParam(3))) {
            return $this->listAll();
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
            return true;
        }


        // record not found
        if (false === $this->model->loadFromCode($this->getUriParam(3))) {
            $this->setError(Tools::lang()->trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return false;
        }

        $data = $this->model->toArray();
        $data['download'] = $this->model->url('download');
        $data['download-permanent'] = $this->model->url('download-permanent');
        $this->returnResult($data);
        return true;
    }

    public function doPOST(): bool
    {
        $field = $this->model->primaryColumn();
        $values = $this->request->request->all();
        $files = $this->request->files->all();

        $param0 = empty($this->getUriParam(3)) ? '' : $this->getUriParam(3);
        $code = $values[$field] ?? $param0;
        if ($this->model->loadFromCode($code)) {
            $this->setError(Tools::lang()->trans('duplicate-record'), $this->model->toArray());
            return false;
        } elseif (empty($values) && empty($files)) {
            $this->setError(Tools::lang()->trans('no-data-received-form'));
            return false;
        }
        //recorremos los archivos recibidos
        foreach ($this->request->files->all() as $file) {
            if (!$file->isValid()) {
                continue;
            }
            //si el archivo es php saltamos
            if ($file->extension() === 'php') {
                continue;
            }
            $file->move('MyFiles', $file->getClientOriginalName());
            $this->model->path = $file->getClientOriginalName();
        }
        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
        }

        return $this->saveResource();
    }

    public function doPUT(): bool
    {
        $field = $this->model->primaryColumn();
        $values = $this->request->request->all();

        $param0 = empty($this->getUriParam(3)) ? '' : $this->getUriParam(3);
        $code = $values[$field] ?? $param0;
        if (false === $this->model->loadFromCode($code)) {
            $this->setError(Tools::lang()->trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return false;
        } elseif (empty($values)) {
            $this->setError(Tools::lang()->trans('no-data-received-form'));
            return false;
        }

        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
        }

        return $this->saveResource();
    }

    private function getRequestArray($key, $default = ''): array
    {
        $array = $this->request->getArray($key, $default);
        return is_array($array) ? $array : []; // if is string has bad format
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
            }
            elseif (substr($key, -8) == '_notnull') {
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

    protected function listAll(): bool
    {
        $filter = $this->getRequestArray('filter');
        $limit = (int)$this->request->get('limit', 50);
        $offset = (int)$this->request->get('offset', 0);
        $operation = $this->getRequestArray('operation');
        $order = $this->getRequestArray('sort');

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
        $this->response->headers->set('X-Total-Count', $count);

        $this->returnResult($data);
        return true;
    }

    protected function returnResult(array $data)
    {
        $this->response->setContent(json_encode($data));
        $this->response->setHttpCode(Response::HTTP_OK);
    }

    private function saveResource(): bool
    {
        if ($this->model->save()) {
            $this->setOk(Tools::lang()->trans('record-updated-correctly'), $this->model->toArray());
            return true;
        }

        $message = Tools::lang()->trans('record-save-error');
        foreach (Tools::log()->read('', ['critical', 'error', 'info', 'notice', 'warning']) as $log) {
            $message .= ' - ' . $log['message'];
        }

        $this->setError($message, $this->model->toArray());
        return false;
    }

    protected function setError(string $message, ?array $data = null, int $status = Response::HTTP_BAD_REQUEST)
    {
        Tools::log('api')->error($message);

        $res = ['error' => $message];
        if ($data !== null) {
            $res['data'] = $data;
        }

        $this->response->setContent(json_encode($res));
        $this->response->setHttpCode($status);
    }

    protected function setOk(string $message, ?array $data = null)
    {
        Tools::log('api')->notice($message);

        $res = ['ok' => $message];
        if ($data !== null) {
            $res['data'] = $data;
        }

        $this->response->setContent(json_encode($res));
        $this->response->setHttpCode(Response::HTTP_OK);
    }
}
