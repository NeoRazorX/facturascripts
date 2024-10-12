<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Tools;
use ZipArchive;

/**
 * Controller to list the items in the AttachedFile model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class ListAttachedFile extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'library';
        $data['icon'] = 'fa-solid fa-book-open';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsFiles();

        $this->showStorageLimitWarning();
    }

    protected function createViewsFiles(string $viewName = 'ListAttachedFile'): void
    {
        $this->addView($viewName, 'AttachedFile', 'attached-files', 'fa-solid fa-paperclip')
            ->addSearchFields(['filename', 'mimetype'])
            ->addOrderBy(['idfile'], 'code')
            ->addOrderBy(['date', 'hour'], 'date', 2)
            ->addOrderBy(['filename'], 'file-name')
            ->addOrderBy(['size'], 'size');

        // filters
        $this->addFilterPeriod($viewName, 'date', 'period', 'date');

        $types = $this->codeModel->all('attached_files', 'mimetype', 'mimetype');
        $this->addFilterSelect($viewName, 'mimetype', 'type', 'mimetype', $types);

        // buttons
        $this->addButton($viewName, [
            'action' => 'download',
            'icon' => 'fa-solid fa-download',
            'label' => 'download'
        ]);
    }

    protected function downloadAction(): bool
    {
        $codes = $this->request->request->getArray('codes');
        if (empty($codes)) {
            Tools::log()->warning('no-selected-item');
            return true;
        }

        // creamos el zip
        $zip = new ZipArchive();
        $filename = 'attached-files.zip';
        $filepath = FS_FOLDER . '/MyFiles/' . $filename;
        if ($zip->open($filepath, ZipArchive::CREATE) !== true) {
            Tools::log()->warning('error-creating-zip-file');
            return true;
        }

        // añadimos los archivos
        $model = $this->views[$this->active]->model;
        foreach ($codes as $code) {
            $file = $model->get($code);
            if ($file) {
                $zip->addFile($file->getFullPath(), $file->idfile . '_' . $file->filename);
            }
        }

        // cerramos el zip
        $zip->close();

        // descargamos el zip
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);

        // borramos el zip
        unlink($filepath);

        $this->setTemplate(false);
        return false;
    }

    protected function execPreviousAction($action)
    {
        if ($action === 'download') {
            return $this->downloadAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function showStorageLimitWarning(): void
    {
        $limit = AttachedFile::getStorageLimit();
        if (empty($limit)) {
            return;
        }

        // si el usado está cerca del límite (80%) mostramos un aviso
        $used = AttachedFile::getStorageUsed();
        if ($used > 0.8 * $limit) {
            $free = $limit - $used;
            Tools::log()->warning('storage-limit-almost', [
                '%free%' => Tools::bytes($free),
                '%limit%' => Tools::bytes($limit),
                '%used%' => Tools::bytes($used)
            ]);
        }
    }
}
