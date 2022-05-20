<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
        $data['icon'] = 'fas fa-book-open';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsFiles();
    }

    protected function createViewsFiles(string $viewName = 'ListAttachedFile')
    {
        $this->addView($viewName, 'AttachedFile', 'attached-files', 'fas fa-paperclip');
        $this->addSearchFields($viewName, ['filename', 'mimetype']);
        $this->addOrderBy($viewName, ['idfile'], 'code');
        $this->addOrderBy($viewName, ['date', 'hour'], 'date', 2);
        $this->addOrderBy($viewName, ['filename'], 'file-name');
        $this->addOrderBy($viewName, ['size'], 'size');

        // filters
        $this->addFilterPeriod($viewName, 'date', 'period', 'date');

        $types = $this->codeModel->all('attached_files', 'mimetype', 'mimetype');
        $this->addFilterSelect($viewName, 'mimetype', 'type', 'mimetype', $types);

        // buttons
        $this->addButton($viewName, [
            'action' => 'download',
            'icon' => 'fas fa-download',
            'label' => 'download'
        ]);
    }

    protected function downloadAction(): bool
    {
        $codes = $this->request->request->get('code');
        if (empty($codes)) {
            self::toolBox()::i18nLog()->warning('no-selected-item');
            return true;
        }

        // creamos el zip
        $zip = new ZipArchive();
        $filename = 'attached-files.zip';
        $filepath = FS_FOLDER . '/MyFiles/' . $filename;
        if ($zip->open($filepath, ZipArchive::CREATE) !== true) {
            self::toolBox()->i18nLog()->warning('error-creating-zip-file');
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
}
