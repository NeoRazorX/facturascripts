<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\FileManager;
use FacturaScripts\Core\Base\MyFilesToken;
use finfo;

/**
 * Class to manage attached files.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class AttachedFile extends Base\ModelOnChangeClass
{
    use Base\ModelTrait;

    const MAX_FILENAME_LEN = 100;

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
        $this->date = date(self::DATE_STYLE);
        $this->hour = date(self::HOUR_STYLE);
        $this->size = 0;
    }

    public function delete(): bool
    {
        // eliminamos el archivo
        $fullPath = $this->getFullPath();
        if (file_exists($fullPath) && false === unlink($fullPath)) {
            $this->toolBox()->i18nLog()->warning('cant-delete-file', ['%fileName%' => $this->path]);
            return false;
        }

        // eliminamos las relaciones con los productos
        $productoImageModel = new ProductoImagen();
        $where = [new DataBaseWhere('idfile', $this->idfile)];
        foreach ($productoImageModel->all($where, [], 0, 0) as $productoImage) {
            $productoImage->delete();
        }

        // eliminamos el registro de la base de datos
        return parent::delete();
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

    public function getStorageLimit(): int
    {
        return defined('FS_STORAGE_LIMIT') ? (int)FS_STORAGE_LIMIT : 0;
    }

    public function getStorageUsed(array $exclude = []): int
    {
        $sql = 'SELECT SUM(size) as size FROM ' . static::tableName();
        if ($exclude) {
            $sql .= ' WHERE idfile NOT IN (' . implode(',', $exclude) . ')';
        }
        foreach (static::$dataBase->select($sql) as $row) {
            return (int)$row ['size'];
        }

        return 0;
    }

    public static function primaryColumn(): string
    {
        return 'idfile';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'filename';
    }

    public static function tableName(): string
    {
        return 'attached_files';
    }

    public function test(): bool
    {
        if (empty($this->idfile)) {
            $this->idfile = $this->newCode();
            return $this->setFile() && parent::test();
        }

        $this->filename = self::toolBox()::utils()::noHtml($this->filename);
        $this->mimetype = self::toolBox()::utils()::noHtml($this->mimetype);
        $this->path = self::toolBox()::utils()::noHtml($this->path);
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
        $fixed = self::toolBox()::utils()::noHtml($original);
        if (strlen($fixed) <= static::MAX_FILENAME_LEN) {
            return empty($fixed) ? '' : strtolower($fixed);
        }

        $parts = explode('.', strtolower($fixed));
        $extension = count($parts) > 1 ? end($parts) : '';
        $name = substr($fixed, 0, static::MAX_FILENAME_LEN - strlen('.' . $extension));
        return $name . '.' . $extension;
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
                    unlink(\FS_FOLDER . '/' . $this->previousData['path']);
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
        if (false === FileManager::createFolder($newFolderPath, true)) {
            $this->toolBox()->i18nLog()->critical('cant-create-folder', ['%folderName%' => $newFolder]);
            return false;
        }

        $currentPath = FS_FOLDER . '/MyFiles/' . $this->path;
        if ($this->getStorageLimit() > 0 &&
            filesize($currentPath) + $this->getStorageUsed([$this->idfile]) > $this->getStorageLimit()) {
            $this->toolBox()->i18nLog()->critical('storage-limit-reached');
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
