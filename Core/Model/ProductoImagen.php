<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2012-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile as DinAttachedFile;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;
use Throwable;

/**
 * Description of ProductoImagen
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author José Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ProductoImagen extends Base\ModelClass
{
    use Base\ModelTrait;

    const THUMBNAIL_PATH = '/MyFiles/Tmp/Thumbnails/';

    /** @var int; */
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

        // obtenemos el nombre de la imagen sin la extension
        $name = pathinfo($this->getFile()->filename, PATHINFO_FILENAME);
        if (empty($name)) {
            return true;
        }

        // buscamos todas las imágenes que empiecen por el mismo nombre y las eliminamos
        $path = FS_FOLDER . self::THUMBNAIL_PATH;
        if (file_exists($path)) {
            foreach (scandir($path) as $file) {
                if (strpos($file, $name) === 0) {
                    unlink($path . $file);
                }
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
        if (false === $file->exists() || false === file_exists($file->getFullPath())) {
            return '';
        }

        // comprobamos si existe el directorio
        if (false === file_exists(FS_FOLDER . self::THUMBNAIL_PATH)) {
            mkdir(FS_FOLDER . self::THUMBNAIL_PATH, 0755, true);
        }

        // si la extensión no está entre las permitidas terminamos
        $ext = pathinfo($file->getFullPath(), PATHINFO_EXTENSION);
        if (false === in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            return '';
        }

        // creamos el nuevo nombre del archivo
        $thumbName = pathinfo($file->filename, PATHINFO_FILENAME) . '_' . $width . 'x' . $height . '.' . $ext;

        // si el archivo existe lo devolvemos
        $thumbFile = self::THUMBNAIL_PATH . $thumbName;
        if (file_exists(FS_FOLDER . $thumbFile)) {
            return $this->getThumbnailPath($thumbFile, $token, $permaToken);
        }

        try {
            // redimensionamos la imagen proporcionalmente
            $image = imagecreatefromstring(file_get_contents($file->getFullPath()));
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);
            $ratio = $imageWidth / $imageHeight;
            if ($width / $height > $ratio) {
                $width = intval($height * $ratio);
            } else {
                $height = intval($width / $ratio);
            }
            $thumb = imagecreatetruecolor($width, $height);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $imageWidth, $imageHeight);

            // guardamos la imagen según la extensión
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($thumb, FS_FOLDER . $thumbFile, 90);
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
        } catch (Throwable $th) {
            Tools::log()->error($th->getMessage());
            return '';
        }

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
