<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\ProductoImagen;

/**
 * Controlador de la API para gestionar las imágenes de los productos.
 */
class ApiProductoImagen extends ApiController
{
    /** @var ProductoImagen */
    protected $model;

    public function doDELETE(): void
    {
        if (empty($this->getUriParam(3)) || false === $this->model->load($this->getUriParam(3))) {
            $this->setError(Tools::lang()->trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return;
        }

        if (false === $this->model->delete()) {
            $this->setError(Tools::lang()->trans('record-deleted-error'));
            return;
        }

        $this->setOk(Tools::lang()->trans('record-deleted-correctly'), $this->model->toArray());
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
            $this->setError(Tools::lang()->trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return;
        }

        $data = $this->model->toArray();
        $data['download'] = $this->model->url('download');
        $data['download-permanent'] = $this->model->url('download-permanent');
        $data['thumbnails'] = $this->buildThumbnails($this->model);
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
            $this->setError(Tools::lang()->trans('duplicate-record'), $this->model->toArray());
            return;
        } elseif (empty($values) && empty($files)) {
            $this->setError(Tools::lang()->trans('no-data-received-form'));
            return;
        }

        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
        }

        // si se envía una imagen, la guardamos como archivo adjunto y la usamos en el registro
        $attachedFile = null;
        if (!empty($files)) {
            $uploadFile = $this->getUploadedImage();
            if (null === $uploadFile) {
                $this->setError(Tools::lang()->trans('not-valid-image'));
                return;
            }

            $attachedFile = $this->createAttachedFile($uploadFile);
            if (null === $attachedFile) {
                $this->setError($this->getSaveErrorMessage());
                return;
            }

            $this->model->idfile = $attachedFile->idfile;
        }

        if (false === $this->saveResource()) {
            // no dejamos el archivo subido sin imagen de producto
            $attachedFile?->delete();
            return;
        }

        if ($attachedFile) {
            $this->createFileRelation($attachedFile);
        }
    }

    public function doPUT(): void
    {
        $field = $this->model->primaryColumn();
        $values = $this->request->request->all();

        $param0 = empty($this->getUriParam(3)) ? '' : $this->getUriParam(3);
        $code = $values[$field] ?? $param0;
        if (false === $this->model->load($code)) {
            $this->setError(Tools::lang()->trans('record-not-found'), null, Response::HTTP_NOT_FOUND);
            return;
        } elseif (empty($values)) {
            $this->setError(Tools::lang()->trans('no-data-received-form'));
            return;
        }

        foreach ($values as $key => $value) {
            $this->model->{$key} = $value;
        }

        $this->saveResource();
    }

    private function buildThumbnails(ProductoImagen $item): array
    {
        // solo trabajamos con archivos de tipo imagen
        if (false === $item->getFile()->isImage()) {
            return [];
        }

        // si se reciben medidas, generamos (o reutilizamos) esa miniatura
        $width = $this->request->query->getInt('width', 0);
        $height = $this->request->query->getInt('height', 0);
        if ($width > 0 || $height > 0) {
            $item->getThumbnail($width ?: 100, $height ?: 100);
        }

        // devolvemos todas las miniaturas existentes del archivo
        return $item->getThumbnails();
    }

    private function createAttachedFile(UploadedFile $uploadFile): ?AttachedFile
    {
        // movemos el archivo a MyFiles sin sobrescribir otro con el mismo nombre
        $folder = Tools::folder('MyFiles');
        Tools::folderCheckOrCreate($folder);
        $name = $uploadFile->getClientOriginalName();
        if (file_exists($folder . DIRECTORY_SEPARATOR . $name)) {
            $name = mt_rand(1, 999999) . '_' . $name;
        }

        if (false === $uploadFile->move($folder, $name)) {
            Tools::log()->error('file-move-error', ['%fileName%' => $uploadFile->getClientOriginalName()]);
            return null;
        }

        // al guardarlo, el archivo se mueve a MyFiles/año/mes
        $attachedFile = new AttachedFile();
        $attachedFile->path = $name;
        if ($attachedFile->save()) {
            return $attachedFile;
        }

        if (file_exists($folder . DIRECTORY_SEPARATOR . $name)) {
            unlink($folder . DIRECTORY_SEPARATOR . $name);
        }

        return null;
    }

    private function createFileRelation(AttachedFile $attachedFile): void
    {
        // la relación hace que la imagen aparezca también en los adjuntos del producto
        $relation = new AttachedFileRelation();
        $relation->idfile = $attachedFile->idfile;
        $relation->model = 'Producto';
        $relation->modelid = $this->model->idproducto;
        $relation->modelcode = (string)$this->model->idproducto;
        $relation->nick = empty($this->apiKey->nick) ? null : $this->apiKey->nick;
        if (false === $relation->save()) {
            Tools::log('api')->warning('api: can not link file ' . $attachedFile->idfile . ' to product ' . $this->model->idproducto);
        }
    }

    private function getSaveErrorMessage(): string
    {
        $message = Tools::lang()->trans('record-save-error');
        foreach (Tools::log()->read('', ['critical', 'error', 'info', 'notice', 'warning']) as $log) {
            $message .= ' - ' . $log['message'];
        }

        return $message;
    }

    /**
     * Devuelve la primera imagen válida recibida. Un campo con varios archivos (files[]) llega como array.
     */
    private function getUploadedImage(): ?UploadedFile
    {
        foreach ($this->request->files->all() as $item) {
            foreach (is_array($item) ? $item : [$item] as $file) {
                if ($file->isValid() && $file->isValidImage()) {
                    return $file;
                }
            }
        }

        return null;
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

            // solo aceptamos identificadores simples (columna o tabla.columna)
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $field)) {
                Tools::log('api')->warning('api: invalid filter field name: ' . $field);
                continue;
            }

            $where[] = new Where($field, $value, $operator, $operation[$key]);
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

        // el conector de cada filtro se concatena en el SQL: solo aceptamos AND u OR
        $badOperations = array_keys(array_filter($operation, fn($value) => false === Where::isValidOperation($value)));
        if (!empty($badOperations)) {
            $this->setError('api: operation not allowed: ' . implode(', ', $badOperations));
            return;
        }

        // obtenemos los registros
        $where = $this->getWhereValues($filter, $operation);
        $data = [];
        foreach ($this->model->all($where, $order, $offset, $limit) as $item) {
            $raw = $item->toArray();
            $raw['download'] = $item->url('download');
            $raw['download-permanent'] = $item->url('download-permanent');
            $raw['thumbnails'] = $this->buildThumbnails($item);
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

    protected function runResource(): void
    {
        $this->model = new ProductoImagen();

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

    private function saveResource(): bool
    {
        if ($this->model->save()) {
            $this->setOk(Tools::lang()->trans('record-updated-correctly'), $this->model->toArray());
            return true;
        }

        $this->setError($this->getSaveErrorMessage(), $this->model->toArray());
        return false;
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
