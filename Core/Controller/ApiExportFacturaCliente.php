<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\FacturaCliente;

class ApiExportFacturaCliente extends ApiController
{
    protected function runResource(): void
    {
        // si el método no es GET, devolvemos un error
        if (false === $this->request->isMethod(Request::METHOD_GET)) {
            $this->response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
            ]));
            return;
        }

        $code = $this->getUriParam(3);
        if (empty($code)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'No invoice selected',
            ]));
            return;
        }

        $facturaCliente = new FacturaCliente();
        if (false === $facturaCliente->loadFromCode($code)) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Invoice not found',
            ]));
            return;
        }

        $type = $this->request->query->get('type', 'PDF');
        $format = (int)$this->request->query->get('format', 0);
        $lang = $this->request->query->get('lang', $facturaCliente->getSubject()->langcode) ?? '';
        $title = Tools::lang($lang)->trans('invoice') . ' ' . $facturaCliente->primaryDescription();

        $exportManager = new ExportManager();
        $exportManager->newDoc($type, $title, $format, $lang);
        $exportManager->addBusinessDocPage($facturaCliente);

        // devolvemos la respuesta
        $exportManager->show($this->response);
    }
}
