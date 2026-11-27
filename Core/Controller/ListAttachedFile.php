<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Controlador para listar los elementos del modelo AttachedFile
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
        $this->tab($viewName)->addButton([
            'action' => 'download',
            'icon' => 'fa-solid fa-download',
            'label' => 'download'
        ]);
    }

    /**
     * Crea un zip temporal con los archivos indicados y devuelve su ruta,
     * o null si no se pudo crear o no contiene ningún archivo.
     *
     * @param AttachedFile $model
     * @param array $codes
     *
     * @return string|null
     */
    protected function createZip($model, array $codes): ?string
    {
        // nombre único por petición, para no mezclar archivos de dos descargas
        $folder = Tools::folder('MyFiles', 'Tmp');
        Tools::folderCheckOrCreate($folder);
        $filepath = $folder . DIRECTORY_SEPARATOR . 'attached-files-' . Tools::randomString(20) . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Tools::log()->warning('error-creating-zip-file');
            return null;
        }

        // añadimos los archivos
        $added = 0;
        foreach ($codes as $code) {
            $file = $model->get($code);
            // solo añadimos archivos que estén dentro de MyFiles
            if (
                $file && $this->isInsideMyFiles($file->getFullPath()) &&
                $zip->addFile($file->getFullPath(), $file->idfile . '_' . $file->filename)
            ) {
                $added++;
            }
        }

        // un zip sin archivos no llega a crearse en disco
        if (false === $zip->close() || 0 === $added || false === file_exists($filepath)) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            Tools::log()->warning('no-data');
            return null;
        }

        return $filepath;
    }

    protected function downloadAction(): bool
    {
        if (false === $this->permissions->allowExport) {
            Tools::log()->warning('no-print-permission');
            return true;
        }

        $codes = $this->request->request->getArray('codes');
        if (empty($codes)) {
            Tools::log()->warning('no-selected-item');
            return true;
        }

        // creamos el zip
        $filepath = $this->createZip($this->activeTab()->model, $codes);
        if (null === $filepath) {
            return true;
        }

        // descargamos el zip
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="attached-files.zip"');
        header('Content-Length: ' . filesize($filepath));

        // descartamos los buffers de salida para no cargar el archivo entero en memoria
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

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

    private function isInsideMyFiles(string $filePath): bool
    {
        $realPath = realpath($filePath);
        $folder = realpath(FS_FOLDER . '/MyFiles');
        return $realPath !== false && $folder !== false && str_starts_with($realPath, $folder . DIRECTORY_SEPARATOR);
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
