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

namespace FacturaScripts\Core\Lib\ExtendedController;

use Exception;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\ProductoImagen;

/**
 * Auxiliar Method for images of the product.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
trait ProductImagesTrait
{
    abstract protected function addHtmlView(string $viewName, string $fileName, string $modelName, string $viewTitle, string $viewIcon = 'fab fa-html5');

    abstract protected function validateFormToken(): bool;

    /**
     * Add a list of images.
     *
     * @return bool
     */
    protected function addImageAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            ToolBox::i18nLog()->warning('not-allowed-modify');
            return false;
        } elseif (false === $this->validateFormToken()) {
            return false;
        }

        $count = 0;
        $uploadFiles = $this->request->files->get('newfiles', []);
        foreach ($uploadFiles as $uploadFile) {
            if (false === $uploadFile->isValid()) {
                ToolBox::log()->error($uploadFile->getErrorMessage());
                continue;
            }

            if (false === strpos($uploadFile->getMimeType(), 'image/')) {
                ToolBox::i18nLog()->error('file-not-supported');
                continue;
            }

            try {
                $uploadFile->move(FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles', $uploadFile->getClientOriginalName());
                $idfile = $this->createAttachedFile($uploadFile->getClientOriginalName());
                if (empty($idfile)) {
                    ToolBox::i18nLog()->error('record-save-error');
                    break;
                }

                $idproduct = $this->createProductImage($idfile);
                if (empty($idproduct)) {
                    ToolBox::i18nLog()->error('record-save-error');
                    break;
                }

                $this->createFileRelation($idproduct, $idfile);
                ++$count;
            } catch (Exception $exc) {
                ToolBox::i18nLog()->error($exc->getMessage());
                return true;
            }
        }

        ToolBox::i18nLog()->notice('images-added-correctly', ['%count%' => $count]);
        return true;
    }

    /**
     * Add view for product images.
     *
     * @param string $viewName
     */
    protected function createViewsProductImages(string $viewName = 'EditProductoImagen')
    {
        $this->addHtmlView($viewName, 'Tab/ProductoImagen', 'ProductoImagen', 'images', 'fas fa-images');
    }

    /**
     * Delete an image.
     *
     * @return bool
     */
    protected function deleteImageAction(): bool
    {
        if (false === $this->permissions->allowDelete) {
            ToolBox::i18nLog()->warning('not-allowed-delete');
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
            ToolBox::i18nLog()->notice('record-deleted-correctly');
            return true;
        }

        $this->dataBase->rollback();
        ToolBox::i18nLog()->error('record-delete-error');
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
    protected function createFileRelation(int $idproduct, int $idfile)
    {
        $fileRelation = new AttachedFileRelation();
        $fileRelation->idfile = $idfile;
        $fileRelation->model = 'Producto';
        $fileRelation->modelid = $idproduct;
        $fileRelation->nick = $this->user->nick;
        $fileRelation->save();
    }
}
