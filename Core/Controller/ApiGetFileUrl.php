<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Lib\MyFilesToken;

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

        $id = $this->getUriParam(3);
        $isProduct = false;

        $archivos = new AttachedFile();
        $archivos->loadFromCode($id);
        $permanentUrl = $archivos->path . '?myft=' . MyFilesToken::get($archivos->path, true);
        $url = $archivos->path . '?myft=' . MyFilesToken::get($archivos->path, false);

        $idFile = $archivos->idfile;

        $fileRelation = new AttachedFileRelation();
        $fileRelations = $fileRelation->all([new DataBaseWhere('idfile', $idFile)]);
        if ($fileRelations[0]->model === 'Producto') {
            $isProduct = true;
        }

        $productoId = $fileRelations[0]->modelid;
        $productos = new Producto();
        $relationProducto = $productos->all([new DataBaseWhere('idproducto', $productoId)]);

        if ($isProduct){
            $this->response->setContent(json_encode([
                'idproducto' => $relationProducto[0]->idproducto,
                'referencia' => $relationProducto[0]->referencia,
                'descripcion' => $relationProducto[0]->descripcion,
                'idfile' => $idFile,
                'path' => $archivos->path,
                'url' => $url,
                'urlpermanente' => $permanentUrl
            ]));
        }
        else {
            $this->response->setContent(json_encode([
                'idfile' => $idFile,
                'path' => $archivos->path,
                'url' => $url,
                'urlpermanente' => $permanentUrl
            ]));
        }
    }
}