<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Core\Model\AttachedFileRelation;

/**
 * Description of DocFilesTrait
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait DocFilesTrait
{

    abstract protected function addHtmlView($viewName, $fileName, $modelName, $viewTitle, $viewIcon = 'fab fa-html5');

    abstract public function getModelClassName();

    abstract public static function toolBox();

    /**
     * 
     * @return bool
     */
    private function addFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        }

        /// duplicated request?
        if ($this->multiRequestProtection->tokenExist($this->request->request->get('multireqtoken', ''))) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return true;
        }

        $uploadFile = $this->request->files->get('new-file');
        if ($uploadFile && $uploadFile->move(\FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles', $uploadFile->getClientOriginalName())) {
            $newFile = new AttachedFile();
            $newFile->path = $uploadFile->getClientOriginalName();
            if (false === $newFile->save()) {
                $this->toolBox()->i18nLog()->error('fail');
                return true;
            }

            $fileRelation = new AttachedFileRelation();
            $fileRelation->idfile = $newFile->idfile;
            $fileRelation->model = $this->getModelClassName();
            $fileRelation->modelid = $this->request->query->get('code');
            $fileRelation->nick = $this->user->nick;
            $fileRelation->observations = $this->request->request->get('observations');
            if (false === $fileRelation->save()) {
                $this->toolBox()->i18nLog()->error('fail');
                return true;
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        return true;
    }

    /**
     * 
     * @param string $viewName
     */
    private function createViewDocFiles(string $viewName = 'docfiles')
    {
        $this->addHtmlView($viewName, 'Tab/DocFiles', 'AttachedFileRelation', 'files', 'fas fa-paperclip');
    }

    /**
     * 
     * @return bool
     */
    private function deleteFileAction(): bool
    {
        if (false === $this->permissions->allowDelete) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if ($fileRelation->loadFromCode($id)) {
            $file = $fileRelation->getFile();
            $fileRelation->delete();
            $file->delete();
        }

        $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
        return true;
    }

    /**
     * 
     * @return bool
     */
    private function editFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        }

        /// duplicated request?
        if ($this->multiRequestProtection->tokenExist($this->request->request->get('multireqtoken', ''))) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if ($fileRelation->loadFromCode($id)) {
            $fileRelation->observations = $this->request->request->get('observations');
            $fileRelation->save();
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        return true;
    }

    /**
     * 
     * @param BaseView $view
     * @param string   $model
     * @param string   $modelid
     */
    private function loadDataDocFiles($view, $model, $modelid)
    {
        $where = [
            new DataBaseWhere('model', $model),
            new DataBaseWhere('modelid', $modelid)
        ];
        $view->loadData('', $where, ['creationdate' => 'DESC']);
    }

    /**
     * 
     * @return bool
     */
    private function unlinkFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        }

        $fileRelation = new AttachedFileRelation();
        $id = $this->request->request->get('id');
        if ($fileRelation->loadFromCode($id)) {
            $fileRelation->delete();
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        return true;
    }
}
