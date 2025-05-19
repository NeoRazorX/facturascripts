<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\ProductoImagen;

/**
 * Auxiliar Method for images of the product.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
trait ProductImagesTrait
{
    abstract protected function addHtmlView(string $viewName, string $fileName, string $modelName, string $viewTitle, string $viewIcon = 'fa-brands fa-html5');

    abstract protected function validateFormToken(): bool;

    /**
     * Add a list of images.
     *
     * @return bool
     */
    protected function addImageAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        } elseif (false === $this->validateFormToken()) {
            return false;
        }

        $count = 0;
        $uploadFiles = $this->request->files->getArray('newfiles');
        foreach ($uploadFiles as $uploadFile) {
            if (false === $uploadFile->isValid()) {
                Tools::log()->error($uploadFile->getErrorMessage());
                continue;
            }

            if (false === strpos($uploadFile->getMimeType(), 'image/')) {
                Tools::log()->error('file-not-supported');
                continue;
            }

            try {
                $uploadFile->move(FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles', $uploadFile->getClientOriginalName());
                $idfile = $this->createAttachedFile($uploadFile->getClientOriginalName());
                if (empty($idfile)) {
                    Tools::log()->error('record-save-error');
                    return true;
                }

                $idproduct = $this->createProductImage($idfile);
                if (empty($idproduct)) {
                    Tools::log()->error('record-save-error');
                    return true;
                }

                $this->createFileRelation($idproduct, $idfile);
                ++$count;
            } catch (Exception $exc) {
                Tools::log()->error($exc->getMessage());
                return true;
            }
        }

        Tools::log()->notice('images-added-correctly', ['%count%' => $count]);
        return true;
    }

    /**
     * Add view for product images.
     *
     * @param string $viewName
     */
    protected function createViewsProductImages(string $viewName = 'EditProductoImagen'): void
    {
        $this->addHtmlView($viewName, 'Tab/ProductoImagen', 'ProductoImagen', 'images', 'fa-solid fa-images');
    }

    /**
     * Delete an image.
     *
     * @return bool
     */
    protected function deleteImageAction(): bool
    {
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return false;
        }

        $id = $this->request->request->get('idimage');
        $productImage = new ProductoImagen();
        if (false === $productImage->loadFromCode($id)) {
            return true;
        }

        $this->dataBase->beginTransaction();
        if ($productImage->delete() && $productImage->getFile()->delete()) {
            $this->dataBase->commit();
            Tools::log()->notice('record-deleted-correctly');
            return true;
        }

        $this->dataBase->rollback();
        Tools::log()->error('record-delete-error');
        return true;
    }

    /**
     * Create the record in the AttachedFile model
     * and returns its identifier.
     *
     * @param string $path
     * @return int
     */
    protected function createAttachedFile(string $path): int
    {
        $newFile = new AttachedFile();
        $newFile->path = $path;
        $newFile->save();
        return $newFile->idfile;
    }

    /**
     * Create the record in the ProductoImagen model
     * and returns its idproducto.
     *
     * @param int $idfile
     * @return ?int
     */
    protected function createProductImage(int $idfile): ?int
    {
        $productImage = new ProductoImagen();
        $productImage->idproducto = $this->request->request->get('idproducto');
        $productImage->idfile = $idfile;

        $reference = $this->request->request->get('referencia', '');
        $productImage->referencia = empty($reference) ? null : $reference;
        return $productImage->save() ? $productImage->idproducto : null;
    }

    /**
     * Create the record in the AttachedFileRelation model.
     *
     * @param int $idproduct
     * @param int $idfile
     */
    protected function createFileRelation(int $idproduct, int $idfile): void
    {
        $fileRelation = new AttachedFileRelation();
        $fileRelation->idfile = $idfile;
        $fileRelation->model = 'Producto';
        $fileRelation->modelid = $idproduct;
        $fileRelation->nick = $this->user->nick;
        $fileRelation->save();
    }
}
