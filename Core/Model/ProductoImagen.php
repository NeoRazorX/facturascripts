<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2012-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\AttachedFile as DinAttachedFile;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of ProductoImagen
 *
 * @author José Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ProductoImagen extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var int; */
    public $id;

    /** @var int */
    public $idfile;

    /** @var int */
    public $idproducto;

    /** @var string */
    public $referencia;

    public function getFile(): AttachedFile
    {
        $file = new DinAttachedFile();
        $file->loadFromCode($this->idfile);
        return $file;
    }

    public function getProducto(): Producto
    {
        $producto = new DinProducto();
        $producto->loadFromCode($this->idproducto);
        return $producto;
    }

    public function getMaxFileUpload(): float
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    public function install(): string
    {
        // dependencias
        new AttachedFile();
        new Producto();
        new Variante();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'productos_imagenes';
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
