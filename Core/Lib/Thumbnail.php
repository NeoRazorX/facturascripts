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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Controller\Myfiles;
use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Tools;
use Throwable;

/**
 * Thumbnail generator
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Thumbnail
{
    public const THUMBNAIL_PATH = '/MyFiles/Tmp/Thumbnails/';

    /**
     * Returns the expected thumbnail path
     * 
     * Warning: this function does not check if the file exists
     * 
     * if path is wrong it returns an empty string
     * 
     * if is not a jpg, jpeg, png or gif it returns an empty string
     * 
     * @param string $filePath
     */
    public static function getExpectedThumbnailPath(string $filePath, int $width, int $height): string
    {
        // si la extensión no está entre las permitidas terminamos
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (false === in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            return '';
        }
        
        // creamos el nuevo nombre del archivo
        $thumbName = pathinfo($filePath, PATHINFO_FILENAME) . '_' . $width . 'x' . $height . '.' . $ext;

        // si el archivo existe lo devolvemos
        $thumbFile = self::THUMBNAIL_PATH . $thumbName;

        return $thumbFile;
    }

    /**
     * Generates a thumbnail and returns its thumbnail path
     * 
     * Uses Thumbnail::getExpectedThumbnailPath() to get the ideal thumbnail path
     * 
     * if it is not possible to generate the thumbnail it returns an empty string
     * 
     * @param string $filePath the path to the file
     * @param int $width width of the thumbnail
     * @param int $height height of the thumbnail
     * @param bool $token if you want to add a token
     * @param bool $permaToken if you want the token to be permanent
     */
    public static function generate(string $filePath, int $width = 100, int $height = 100, bool $token = false, bool $permaToken = false): string
    {
        // comprobamos si no existe la imagen
        if (false === file_exists($filePath)) {
            return '';
        }

        // comprobamos si existe el directorio
        if (false === file_exists(FS_FOLDER . self::THUMBNAIL_PATH)) {
            mkdir(FS_FOLDER . self::THUMBNAIL_PATH, 0755, true);
        }

        // si el archivo existe lo devolvemos
        $thumbFile = Thumbnail::getExpectedThumbnailPath($filePath, $width, $height);
        if (file_exists(FS_FOLDER . $thumbFile)) {
            return self::getThumbnailPath($thumbFile, $token, $permaToken);
        }

        try {
            // redimensionamos la imagen proporcionalmente
            $image = imagecreatefromstring(file_get_contents($filePath));
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
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
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

        return self::getThumbnailPath($thumbFile, $token, $permaToken);
    }

    /**
     * Refactor for getting the thumbnail url with token if is necesary
     * 
     * @param string $path the path to the thumbnail
     * @param bool $token if you want to add a token
     * @param bool $parmaToken if you want the token to be permanent
     */
    private function getThumbnailPath(string $path, bool $token, bool $parmaToken): string
    {
        if ($token && false === $parmaToken) {
            return $path . '?myft=' . MyFilesToken::get($path, false);
        } elseif ($token && $parmaToken) {
            return $path . '?myft=' . MyFilesToken::get($path, true);
        }

        return $path;
    }
}
