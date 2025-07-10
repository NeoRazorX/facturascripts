<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile;

class ApiGetFileUrl extends ApiController
{
    protected function runResource(): void
    {
        if (false === $this->request->isMethod(Request::METHOD_GET)) {
            $this->response->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
            ]));
            return;
        }

        $archivos = AttachedFile::all();
        $this->response->setContent(json_encode([
            $archivos
        ]));
    }
}