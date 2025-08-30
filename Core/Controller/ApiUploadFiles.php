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

use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile;

class ApiUploadFiles extends ApiController
{
    protected function runResource(): void
    {
        if (!in_array($this->request->method(), ['POST', 'PUT'])) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'Method not allowed',
                ]);
            return;
        }

        $uploadedFiles = [];
        $files = $this->request->files->getArray('files');
        foreach ($files as $file) {
            /** @var UploadedFile $file */
            if ($uploadedFile = $this->uploadFile($file)) {
                $uploadedFiles[] = $uploadedFile;
            }
        }

        // devolvemos la respuesta
        $this->response->json([
            'files' => $uploadedFiles,
        ]);
    }

    private function uploadFile(UploadedFile $uploadFile): ?AttachedFile
    {
        if (false === $uploadFile->isValid()) {
            return null;
        }

        // exclude php files
        if (in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
            return null;
        }

        // check if the file already exists
        $destiny = FS_FOLDER . '/MyFiles/';
        $destinyName = $uploadFile->getClientOriginalName();
        if (file_exists($destiny . $destinyName)) {
            $destinyName = mt_rand(1, 999999) . '_' . $destinyName;
        }

        // move the file to the MyFiles folder
        if ($uploadFile->move($destiny, $destinyName)) {
            $file = new AttachedFile();
            $file->path = $destinyName;
            if ($file->save()) {
                return $file;
            }
        }

        return null;
    }
}
