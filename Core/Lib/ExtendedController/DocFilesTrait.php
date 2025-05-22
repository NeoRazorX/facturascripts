<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\AttachedFileRelation;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AttachedFile;

/**
 * Description of DocFilesTrait
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait DocFilesTrait
{
    abstract protected function addHtmlView(string $viewName, string $fileName, string $modelName, string $viewTitle, string $viewIcon = 'fa-brands fa-html5');

    private function addFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken()) {
            return true;
        }

        $uploadFiles = $this->request->files->getArray('new-files');
        foreach ($uploadFiles as $key => $uploadFile) {
            if (is_null($uploadFile)) {
                continue;
            } elseif (false === $uploadFile->isValid()) {
                Tools::log()->error($uploadFile->getErrorMessage());
                continue;
            }

            // exclude php files
            if (in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
                Tools::log()->error(Tools::lang()->trans('php-files-blocked'));
                continue;
            }

            // check if the file already exists
            $destiny = FS_FOLDER . '/MyFiles/';
            $destinyName = $uploadFile->getClientOriginalName();
            if (file_exists($destiny . $destinyName)) {
                $destinyName = mt_rand(1, 999999) . '_' . $destinyName;
            }

            // move the file to the MyFiles folder
            if (false === $uploadFile->move($destiny, $destinyName)) {
                Tools::log()->error(Tools::lang()->trans('file-not-found'));
                continue;
            }

            $newFile = new AttachedFile();
            $newFile->path = $uploadFile->getClientOriginalName();
            if (false === $newFile->save()) {
                Tools::log()->error('fail');
                return true;
            }

            $fileRelation = new AttachedFileRelation();
            $fileRelation->idfile = $newFile->idfile;
            $fileRelation->model = $this->getModelClassName();
            $fileRelation->modelcode = $this->request->query->get('code');
            $fileRelation->modelid = (int)$fileRelation->modelcode;
            $fileRelation->nick = $this->user->nick;
            $fileRelation->observations = $this->request->request->get('observations');
            $this->pipeFalse('addFileAction', $fileRelation, $this->request);

            if (false === $fileRelation->save()) {
                Tools::log()->error('fail-relation');
                return true;
            }

        }

        // Si se trata de un documento, actualizamos el número de documentos adjuntos.
        if ($this->getModel() instanceof BusinessDocument) {
            $this->updateNumDocs();
        }

        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    protected function createViewDocFiles(string $viewName = 'docfiles', string $template = 'Tab/DocFiles')
    {
        $this->addHtmlView($viewName, $template, 'AttachedFileRelation', 'files', 'fa-solid fa-paperclip');
    }

    private function deleteFileAction(): bool
    {
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return true;
        } elseif (false === $this->validateFileActionToken()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if (false === $fileRelation->loadFromCode($id)) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        if ($fileRelation->modelcode != $this->request->query->get('code') ||
            $fileRelation->model !== $this->getModelClassName()) {
            Tools::log()->warning('not-allowed-delete');
            return true;
        }

        $file = $fileRelation->getFile();
        $fileRelation->delete();
        $file->delete();

        Tools::log()->notice('record-deleted-correctly');

        // Si se trata de un documento, actualizamos el número de documentos adjuntos.
        if ($this->getModel() instanceof BusinessDocument) {
            $this->updateNumDocs();
        }

        return true;
    }

    private function editFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if (false === $fileRelation->loadFromCode($id)) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        if ($fileRelation->modelcode != $this->request->query->get('code') ||
            $fileRelation->model !== $this->getModelClassName()) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        }

        $fileRelation->observations = $this->request->request->get('observations');
        $this->pipeFalse('editFileAction', $fileRelation, $this->request);

        if (false === $fileRelation->save()) {
            Tools::log()->error('record-save-error');
            return true;
        }

        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    /**
     * @param BaseView $view
     * @param string $model
     * @param string $modelid
     */
    private function loadDataDocFiles($view, $model, $modelid)
    {
        $where = [new DataBaseWhere('model', $model)];
        $where[] = is_numeric($modelid) ?
            new DataBaseWhere('modelid|modelcode', $modelid) :
            new DataBaseWhere('modelcode', $modelid);
        $view->loadData('', $where, ['creationdate' => 'DESC']);
    }

    private function unlinkFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if ($fileRelation->loadFromCode($id)) {
            $fileRelation->delete();
        }

        Tools::log()->notice('record-updated-correctly');

        // Si se trata de un documento, actualizamos el número de documentos adjuntos.
        if ($this->getModel() instanceof BusinessDocument) {
            $this->updateNumDocs();
        }

        return true;
    }

    /**
     * Actualiza el número de adjuntos del documento.
     */
    protected function updateNumDocs(): void
    {
        $attachedFileRelation = new AttachedFileRelation();
        $where = [
            new DataBaseWhere('model', $this->getModelClassName()),
            new DataBaseWhere('modelid', $this->request->get('code'))
        ];
        $numDocs = count($attachedFileRelation->all($where, [], 0, 0));

        $model = $this->getModel();
        $model->numdocs = $numDocs;

        if (false === $model->save()) {
            $this->response->setContent(json_encode([
                'ok' => false,
                'messages' => Tools::log()::read('', $this->logLevels)
            ]));
        }
    }

    private function validateFileActionToken(): bool
    {
        // valid request?
        $token = $this->request->request->get('multireqtoken', '');
        if (empty($token) || false === $this->multiRequestProtection->validate($token)) {
            Tools::log()->warning('invalid-request');
            return false;
        }

        // duplicated request?
        if ($this->multiRequestProtection->tokenExist($token)) {
            Tools::log()->warning('duplicated-request');
            return false;
        }

        return true;
    }
}
