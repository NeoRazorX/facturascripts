<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExportManager;

class ApiExportDocument extends ApiController
{
    /** @var string */
    protected $model;

    protected function runResource(): void
    {
        if (false === $this->request->isMethod(Request::METHOD_GET)) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'method-not-allowed',
                ]);
            return;
        }

        if (false === $this->loadModel()) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'resource-not-found',
                ]);
            return;
        }

        $code = $this->getUriParam(3);
        if (empty($code)) {
            $this->response
                ->setHttpCode(Response::HTTP_BAD_REQUEST)
                ->json([
                    'status' => 'error',
                    'message' => 'record-not-specified',
                ]);
            return;
        }

        $class = '\\FacturaScripts\\Dinamic\\Model\\' . $this->model;
        $doc = new $class();
        if (false === $doc->load($code)) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'record-not-found',
                ]);
            return;
        }

        $type = $this->request->query('type', 'PDF');
        $format = (int)$this->request->query('format', 0);
        $lang = $this->request->query('lang', $doc->getSubject()->langcode) ?? '';
        $title = Tools::lang($lang)->trans('invoice') . ' ' . $doc->id();

        $exportManager = new ExportManager();
        $exportManager->newDoc($type, $title, $format, $lang);
        $exportManager->addBusinessDocPage($doc);

        // devolvemos la respuesta
        $exportManager->show($this->response);
    }

    protected function loadModel(): bool
    {
        switch ($this->getUriParam(2)) {
            case 'exportarAlbaranCliente':
                $this->model = 'AlbaranCliente';
                return true;

            case 'exportarAlbaranProveedor':
                $this->model = 'AlbaranProveedor';
                return true;

            case 'exportarFacturaCliente':
                $this->model = 'FacturaCliente';
                return true;

            case 'exportarFacturaProveedor':
                $this->model = 'FacturaProveedor';
                return true;

            case 'exportarPedidoCliente':
                $this->model = 'PedidoCliente';
                return true;

            case 'exportarPedidoProveedor':
                $this->model = 'PedidoProveedor';
                return true;

            case 'exportarPresupuestoCliente':
                $this->model = 'PresupuestoCliente';
                return true;

            case 'exportarPresupuestoProveedor':
                $this->model = 'PresupuestoProveedor';
                return true;
        }

        return false;
    }
}
