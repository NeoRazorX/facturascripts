<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\AttachedFile;

/**
 * Description of DocFilesTrait
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait DocFilesTrait
{

    abstract protected function addHtmlView(string $viewName, string $fileName, string $modelName, string $viewTitle, string $viewIcon = 'fab fa-html5');

    private function addFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            ToolBox::i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken()) {
            return true;
        }

        $uploadFile = $this->request->files->get('new-file');
        if ($uploadFile && $uploadFile->move(FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles', $uploadFile->getClientOriginalName())) {
            $newFile = new AttachedFile();
            $newFile->path = $uploadFile->getClientOriginalName();
            if (false === $newFile->save()) {
                ToolBox::i18nLog()->error('fail');
                return true;
            }

            $fileRelation = new AttachedFileRelation();
            $fileRelation->idfile = $newFile->idfile;
            $fileRelation->model = $this->getModelClassName();
            $fileRelation->modelcode = $this->request->query->get('code');
            $fileRelation->modelid = (int)$fileRelation->modelcode;
            $fileRelation->nick = $this->user->nick;
            $fileRelation->observations = $this->request->request->get('observations');
            if (false === $fileRelation->save()) {
                ToolBox::i18nLog()->error('fail-relation');
                return true;
            }
        }

        ToolBox::i18nLog()->notice('record-updated-correctly');
        return true;
    }

    protected function createViewDocFiles(string $viewName = 'docfiles', string $template = 'Tab/DocFiles')
    {
        $this->addHtmlView($viewName, $template, 'AttachedFileRelation', 'files', 'fas fa-paperclip');
    }

    private function deleteFileAction(): bool
    {
        if (false === $this->permissions->allowDelete) {
            ToolBox::i18nLog()->warning('not-allowed-delete');
            return true;
        } elseif (false === $this->validateFileActionToken()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if ($fileRelation->loadFromCode($id)) {
            $file = $fileRelation->getFile();
            $fileRelation->delete();
            $file->delete();
        }

        ToolBox::i18nLog()->notice('record-deleted-correctly');
        return true;
    }

    private function editFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            ToolBox::i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if ($fileRelation->loadFromCode($id)) {
            $fileRelation->observations = $this->request->request->get('observations');
            $fileRelation->save();
        }

        ToolBox::i18nLog()->notice('record-updated-correctly');
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
            ToolBox::i18nLog()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFileActionToken()) {
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if ($fileRelation->loadFromCode($id)) {
            $fileRelation->delete();
        }

        ToolBox::i18nLog()->notice('record-updated-correctly');
        return true;
    }

    private function validateFileActionToken(): bool
    {
        // valid request?
        $token = $this->request->request->get('multireqtoken', '');
        if (empty($token) || false === $this->multiRequestProtection->validate($token)) {
            ToolBox::i18nLog()->warning('invalid-request');
            return false;
        }

        // duplicated request?
        if ($this->multiRequestProtection->tokenExist($token)) {
            ToolBox::i18nLog()->warning('duplicated-request');
            return false;
        }

        return true;
    }
}
