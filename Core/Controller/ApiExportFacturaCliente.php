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

use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $code = $this->request->query->get('idfactura');
        $requestLang = $this->request->query->get('langcode');

        if (empty($code)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'idfactura field is required',
            ]));
            return;
        }

        $facturaCliente = new FacturaCliente();
        $facturaCliente->loadFromCode($code);

        $title = Tools::lang()->trans('invoice') . ' ' . $facturaCliente->primaryDescription();
        $format = 0; // TODO COMENTAR CON CARLOS

        $subjectLang = $facturaCliente->getSubject()->langcode;
        $lang = $requestLang ?? $subjectLang ?? '';

        $exportManager = new ExportManager();
        $exportManager->newDoc('PDF', $title, $format, $lang);
        $exportManager->addBusinessDocPage($facturaCliente);

        $facturaClientePdfFile = $exportManager->getDoc();

        // devolvemos la respuesta
        $this->response->headers->set('Content-Type', 'application/pdf');
        $this->response->setStatusCode(Response::HTTP_OK);
        $this->response->setContent($facturaClientePdfFile);
    }
}
