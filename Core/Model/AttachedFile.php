<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Model\Base\ModelOnChangeClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use finfo;

/**
 * Class to manage attached files.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class AttachedFile extends ModelOnChangeClass
{
    use ModelTrait;

    const MAX_FILENAME_LEN = 100;
    const STORAGE_USED_KEY = 'storage-used';

    /** @var string */
    public $date;

    /** @var string */
    public $filename;

    /** @var string */
    public $hour;

    /** @var int */
    public $idfile;

    /** @var string */
    public $mimetype;

    /** @var string */
    public $path;

    /** @var int */
    public $size;

    public function clear()
    {
        parent::clear();
        $this->date = Tools::date();
        $this->hour = Tools::hour();
        $this->size = 0;
    }

    public function delete(): bool
    {
        // eliminamos el archivo
        $fullPath = $this->getFullPath();
        if (file_exists($fullPath) && false === unlink($fullPath)) {
            Tools::log()->warning('cant-delete-file', ['%fileName%' => $this->path]);
            return false;
        }

        // eliminamos las relaciones con los productos
        $productoImageModel = new ProductoImagen();
        $where = [new DataBaseWhere('idfile', $this->idfile)];
        foreach ($productoImageModel->all($where, [], 0, 0) as $productoImage) {
            $productoImage->delete();
        }

        // eliminamos el registro de la base de datos
        if (false === parent::delete()) {
            return false;
        }

        // eliminamos el registro de la caché
        Cache::delete(self::STORAGE_USED_KEY);

        return true;
    }

    public function getExtension(): string
    {
        $parts = explode('.', strtolower($this->filename));
        return count($parts) > 1 ? end($parts) : '';
    }

    public function getFullPath(): string
    {
        return FS_FOLDER . '/' . $this->path;
    }

    public static function getStorageLimit(): int
    {
        return Tools::config('storage_limit', 0);
    }

    public static function getStorageUsed(array $exclude = []): int
    {
        return Cache::remember(self::STORAGE_USED_KEY, function () use ($exclude) {
            $size = 0;
            $sql = 'SELECT SUM(size) as size FROM ' . static::tableName();
            if ($exclude) {
                $sql .= ' WHERE idfile NOT IN (' . implode(',', $exclude) . ')';
            }
            foreach (static::$dataBase->select($sql) as $row) {
                $size = (int)$row['size'];
                break;
            }

            $folderSize = Tools::folderSize(Tools::folder('MyFiles'));
            return max($size, $folderSize);
        });
    }

    public function isArchive(): bool
    {
        return in_array($this->mimetype, ['application/zip', 'application/x-rar-compressed']);
    }

    public function isImage(): bool
    {
        return in_array($this->mimetype, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function isPdf(): bool
    {
        return $this->mimetype === 'application/pdf';
    }

    public function isVideo(): bool
    {
        return in_array($this->mimetype, ['video/mp4', 'video/ogg', 'video/webm']);
    }

    public static function primaryColumn(): string
    {
        return 'idfile';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'filename';
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        // eliminamos el registro de la caché
        Cache::delete(self::STORAGE_USED_KEY);

        return true;
    }

    public function shortFileName(int $length = 20): string
    {
        if (strlen($this->filename) <= $length) {
            return $this->filename ?? '';
        }

        $parts = explode('.', $this->filename);
        $extension = count($parts) > 1 ? end($parts) : '';
        $name = substr($this->filename, 0, $length - strlen('...' . $extension));
        return $name . '...' . $extension;
    }

    public static function tableName(): string
    {
        return 'attached_files';
    }

    public function test(): bool
    {
        if (empty($this->idfile)) {
            $this->idfile = $this->getNextCode();
            return $this->setFile() && parent::test();
        }

        $this->filename = Tools::noHtml($this->filename);
        $this->mimetype = Tools::noHtml($this->mimetype);
        $this->path = Tools::noHtml($this->path);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        switch ($type) {
            case 'download':
                return $this->path . '?myft=' . MyFilesToken::get($this->path ?? '', false);

            case 'download-permanent':
                return $this->path . '?myft=' . MyFilesToken::get($this->path ?? '', true);

            default:
                return parent::url($type, $list);
        }
    }

    protected function fixFileName(string $original): string
    {
        $fixed = Tools::noHtml($original);
        if (strlen($fixed) <= static::MAX_FILENAME_LEN) {
            return empty($fixed) ? '' : strtolower($fixed);
        }

        $parts = explode('.', strtolower($fixed));
        $extension = count($parts) > 1 ? end($parts) : '';
        $name = substr($fixed, 0, static::MAX_FILENAME_LEN - strlen('.' . $extension));
        return $name . '.' . $extension;
    }

    protected function getNextCode(): int
    {
        switch (Tools::config('db_type')) {
            case 'mysql':
                $sql = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . Tools::config('db_name')
                    . "' AND TABLE_NAME = '" . static::tableName() . "';";
                foreach (static::$dataBase->select($sql) as $row) {
                    return max($this->newCode(), $row['AUTO_INCREMENT']);
                }
                break;

            case 'postgresql':
                $sql = "SELECT nextval('" . static::tableName() . "_idfile_seq');";
                foreach (static::$dataBase->select($sql) as $row) {
                    return max($this->newCode(), $row['nextval']);
                }
                break;
        }

        return $this->newCode();
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'path':
                if ($this->previousData['path']) {
                    // remove old file
                    unlink(FS_FOLDER . '/' . $this->previousData['path']);
                }
                return $this->setFile();

            default:
                return parent::onChange($field);
        }
    }

    /**
     * Examine and move new file set.
     *
     * @return bool
     */
    protected function setFile(): bool
    {
        $this->filename = $this->fixFileName($this->path);
        $newFolder = 'MyFiles/' . date('Y/m', strtotime($this->date));
        $newFolderPath = FS_FOLDER . '/' . $newFolder;
        if (false === Tools::folderCheckOrCreate($newFolderPath)) {
            Tools::log()->critical('cant-create-folder', ['%folderName%' => $newFolder]);
            return false;
        }

        $currentPath = FS_FOLDER . '/MyFiles/' . $this->path;
        if ($this->getStorageLimit() > 0 &&
            filesize($currentPath) + static::getStorageUsed([$this->idfile]) > $this->getStorageLimit()) {
            Tools::log()->critical('storage-limit-reached');
            unlink($currentPath);
            return false;
        }

        if (empty($this->path) ||
            false === rename($currentPath, $newFolderPath . '/' . $this->idfile . '.' . $this->getExtension())) {
            return false;
        }

        $this->path = $newFolder . '/' . $this->idfile . '.' . $this->getExtension();
        $this->size = filesize($this->getFullPath());
        $info = new finfo();
        $this->mimetype = $info->file($this->getFullPath(), FILEINFO_MIME_TYPE);
        if (strlen($this->mimetype) > 100) {
            $this->mimetype = substr($this->mimetype, 0, 100);
        }

        return true;
    }

    protected function setPreviousData(array $fields = [])
    {
        $more = ['path'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
