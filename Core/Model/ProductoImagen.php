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

use FacturaScripts\Core\Base\MyFilesToken;
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

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        // obtenemos la imagen
        $image = $this->getFile();

        // obtenemos el nombre de la imagen sin la extension
        $name = pathinfo($image->filename, PATHINFO_FILENAME);

        // buscamos todas las imágenes que empiecen por el mismo nombre y las eliminamos
        $path = FS_FOLDER . '/MyFiles/Tmp/Thumbnails/';
        $files = scandir($path);
        foreach ($files as $file) {
            if (strpos($file, $name) === 0) {
                unlink($path . $file);
            }
        }

        return true;
    }

    public function getFile(): AttachedFile
    {
        $file = new DinAttachedFile();
        $file->loadFromCode($this->idfile);
        return $file;
    }

    public function getMaxFileUpload(): float
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    public function getProducto(): Producto
    {
        $producto = new DinProducto();
        $producto->loadFromCode($this->idproducto);
        return $producto;
    }

    public function getThumbnail(int $width = 100, int $height = 100, bool $token = false, bool $permaToken = false): string
    {
        // comprobamos si no existe la imagen
        $file = $this->getFile();
        if (false === $file->exists()) {
            return '';
        }

        $thumbPath = '/MyFiles/Tmp/Thumbnails/';

        // comprobamos si existe el directorio
        if (false === file_exists(FS_FOLDER . $thumbPath)) {
            mkdir(FS_FOLDER . $thumbPath, 0755, true);
        }

        // obtenemos la extension
        $ext = pathinfo($file->getFullPath(), PATHINFO_EXTENSION);

        // si la extensión no está entre las permitidas terminamos
        if (false === in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            return '';
        }

        // obtenemos el nombre del archivo sin la extension
        $fileName = pathinfo($file->filename, PATHINFO_FILENAME);

        // creamos el nuevo nombre del archivo
        $thumbName = $fileName . '_' . $width . 'x' . $height . '.' . $ext;

        // si el archivo existe lo devolvemos
        $thumbFile = $thumbPath . $thumbName;
        if (file_exists(FS_FOLDER . $thumbFile)) {
            return $this->getThumbnailPath($thumbFile, $token, $permaToken);
        }

        // redimensionamos la imagen proporcionalmente
        $image = imagecreatefromstring(file_get_contents($file->getFullPath()));
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        $ratio = $imageWidth / $imageHeight;
        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }
        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $imageWidth, $imageHeight);

        // guardamos la imagen según la extensión
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumb, FS_FOLDER . $thumbFile);
                break;

            case 'png':
                imagepng($thumb, FS_FOLDER . $thumbFile);
                break;

            case 'gif':
                imagegif($thumb, FS_FOLDER . $thumbFile);
                break;
        }

        imagedestroy($image);
        imagedestroy($thumb);
        return $this->getThumbnailPath($thumbFile, $token, $permaToken);
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

    protected function getThumbnailPath(string $path, bool $token, bool $parmaToken): string
    {
        if ($token && false === $parmaToken) {
            return $path . '?myft=' . MyFilesToken::get($path, false);
        } elseif ($token && $parmaToken) {
            return $path . '?myft=' . MyFilesToken::get($path, true);
        }
        return $path;
    }
}
