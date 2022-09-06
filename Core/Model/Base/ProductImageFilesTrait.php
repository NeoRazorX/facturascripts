<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use Exception;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\ProductImage;

/**
 * Auxiliar Method for images of the product.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
trait ProductImageFilesTrait
{

    abstract protected function addHtmlView(string $viewName, string $fileName, string $modelName, string $viewTitle, string $viewIcon = 'fab fa-html5');

    abstract public static function toolBox();

    /**
     *
     * @return bool
     */
    protected function addFileAction(): bool
    {
        if (false === $this->checkFileAction()) {
            return true;
        }

        $count = 0;
        $uploadFiles = $this->request->files->get('newfiles', []);
        foreach ($uploadFiles as $uploadFile) {
            if (false === $uploadFile->isValid()) {
                $this->toolBox()->log()->error($uploadFile->getErrorMessage());
                continue;
            }

            if (false === strpos($uploadFile->getMimeType(), 'image/')) {
                $this->toolBox()->i18nLog()->error('file-not-supported');
                continue;
            }

            if ($uploadFile->move(FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles', $uploadFile->getClientOriginalName())) {
                $this->dataBase->beginTransaction();
                try {
                    $idfile = $this->createFileAttached($uploadFile->getClientOriginalName());
                    $idproduct = $this->createProductImage($idfile);
                    $this->createFileRelation($idproduct, $idfile);
                    $this->dataBase->commit();
                    ++$count;
                } catch (\Exception $ex) {
                    $this->dataBase->rollback();
                    $this->toolBox()->i18nLog()->error($ex->getMessage());
                    // FIXME: Remove, if exists, uploadfile
                }
            }
        }
        $this->toolBox()->i18nLog()->notice('images-updated-correctly', ['%count%' => $count]);
        return true;
    }

    /**
     *
     * @return bool
     */
    protected function deleteFileAction(): bool
    {
        if (false === $this->permissions->allowDelete) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            return true;
        }

        $idimage = $this->request->request->get('idimage');
        $productImage = new ProductImage();
        if (false === $productImage->loadFromCode($idimage)) {
            return true;
        }

        $fileRelation = $productImage->getFile();
        $file = $fileRelation->getFile();
        $this->dataBase->beginTransaction();
        try {
            // FK of product image delete fileRelation.
            // need delete firt product image to avoid error when setting null the idfile.
            if ($productImage->delete() && $file->delete()) {
                $this->dataBase->commit();
                $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
            }
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }
        return true;
    }

    /**
     *
     * @return bool
     */
    private function checkFileAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        $token = $this->request->request->get('multireqtoken', '');
        if (empty($token) || false === $this->multiRequestProtection->validate($token)) {
            $this->toolBox()->i18nLog()->warning('invalid-request');
            return false;
        }

        if ($this->multiRequestProtection->tokenExist($token)) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return false;
        }
        return true;
    }

    /**
     *
     * @return int
     * @throws Exception
     */
    private function createFileAttached(string $path): int
    {
        $newFile = new AttachedFile();
        $newFile->path = $path;
        if (false === $newFile->save()) {
            throw new Exception();
        }
        return $newFile->idfile;
    }

    /**
     *
     * @param int $idfile
     * @return int
     * @throws Exception
     */
    private function createProductImage(int $idfile): int
    {
        $productImage = new ProductImage();
        $productImage->idproducto = $this->request->request->get('idproducto');
        $productImage->idfile = $idfile;

        $reference = $this->request->request->get('referencia', '');
        $productImage->referencia = empty($reference) ? null : $reference;
        if (false === $productImage->save()) {
            throw new Exception();
        }
        return $productImage->idimage;
    }

    /**
     *
     * @param int $idproduct
     * @param int $idfile
     * @throws Exception
     */
    private function createFileRelation(int $idproduct, int $idfile)
    {
        $fileRelation = new AttachedFileRelation();
        $fileRelation->idfile = $idfile;
        $fileRelation->model = 'ProductImage';
        $fileRelation->modelid = $idproduct;
        $fileRelation->nick = $this->user->nick;
        if (false === $fileRelation->save()) {
            throw new Exception();
        }
    }

    /**
     *
     * @param string $viewName
     */
    private function createViewEmployeeFiles(string $viewName = 'EditProductImage')
    {
        $this->addHtmlView($viewName, 'Tab/ProductImage', 'Join\ProductImage', 'images', 'fas fa-images');
    }
}
