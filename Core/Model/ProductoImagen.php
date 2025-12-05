<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2012-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Lib\Thumbnail;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile as DinAttachedFile;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;

/**
 * Description of ProductoImagen
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author José Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ProductoImagen extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var int */
    public $idfile;

    /** @var int */
    public $idproducto;

    /** @var string */
    public $referencia;

    /** @var int */
    public $orden;

    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Inicialmente el orden es el id
        // hasta que se asigne un orden en concreto.
        $this->orden = $this->orden ?? $this->id;
    }

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        Thumbnail::delete($this->getFile()->getFullPath());
        return true;
    }

    public function getFile(): AttachedFile
    {
        $file = new DinAttachedFile();
        $file->load($this->idfile);
        return $file;
    }

    public function getMaxFileUpload(): float
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    public function getProducto(): Producto
    {
        $producto = new DinProducto();
        $producto->load($this->idproducto);
        return $producto;
    }

    public function getThumbnail(int $width = 100, int $height = 100, bool $token = false, bool $permaToken = false): string
    {
        $file = $this->getFile();
        if (false === $file->exists() || false === file_exists($file->getFullPath())) {
            return '';
        }

        return Thumbnail::generate($file->getFullPath(), $width, $height, $token, $permaToken);
    }

    public function install(): string
    {
        // dependencias
        new AttachedFile();
        new Producto();
        new Variante();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'productos_imagenes';
    }

    public function test(): bool
    {
        // si el archivo no es una imagen, devolvemos false
        if (false === $this->getFile()->isImage()) {
            Tools::log()->error('not-valid-image');
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        switch ($type) {
            case 'download':
                return $this->getFile()->url('download');

            case 'download-permanent':
                return $this->getFile()->url('download-permanent');

            default:
                return parent::url($type, $list);
        }
    }
}
