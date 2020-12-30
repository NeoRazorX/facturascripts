<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\FileManager;

/**
 * Class to manage attached files.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class AttachedFile extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;

    /**
     * Date.
     *
     * @var string
     */
    public $date;

    /**
     * Contains the file name.
     *
     * @var string
     */
    public $filename;

    /**
     * Hour.
     *
     * @var string
     */
    public $hour;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idfile;

    /**
     * Content the mime content type.
     *
     * @var string
     */
    public $mimetype;

    /**
     * Contains the relative path to file.
     *
     * @var string
     */
    public $path;

    /**
     * The size of the file in bytes.
     *
     * @var int
     */
    public $size;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->date = \date(self::DATE_STYLE);
        $this->hour = \date(self::HOUR_STYLE);
        $this->size = 0;
    }

    /**
     * Remove the model data from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $fullPath = $this->getFullPath();
        if (\file_exists($fullPath) && false === \unlink($fullPath)) {
            $this->toolBox()->i18nLog()->warning('cant-delete-file', ['%fileName%' => $this->path]);
            return false;
        }

        return parent::delete();
    }

    /**
     * 
     * @return string
     */
    public function getFullPath()
    {
        return \FS_FOLDER . DIRECTORY_SEPARATOR . $this->path;
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idfile';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'filename';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'attached_files';
    }

    /**
     * Test model data.
     *
     * @return bool
     */
    public function test()
    {
        if (empty($this->idfile)) {
            $this->idfile = $this->newCode();
            $this->setFile();
        }

        return parent::test();
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'path':
                if ($this->previousData['path']) {
                    /// remove old file
                    \unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . $this->previousData['path']);
                }
                return $this->setFile();

            default:
                return parent::onChange($field);
        }
    }

    /**
     * Examine and move new file setted.
     * 
     * @return bool
     */
    protected function setFile()
    {
        $this->filename = $this->path;
        $newFolder = 'MyFiles' . DIRECTORY_SEPARATOR . \date('Y' . DIRECTORY_SEPARATOR . 'm', \strtotime($this->date));
        $newFolderPath = \FS_FOLDER . DIRECTORY_SEPARATOR . $newFolder;
        if (false === FileManager::createFolder($newFolderPath, true)) {
            $this->toolBox()->i18nLog()->critical('cant-create-folder', ['%folderName%' => $newFolder]);
            return false;
        }

        $currentPath = \FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . $this->path;
        if (false === \rename($currentPath, $newFolderPath . DIRECTORY_SEPARATOR . $this->idfile)) {
            return false;
        }

        $this->path = $newFolder . DIRECTORY_SEPARATOR . $this->idfile;
        $this->size = \filesize($this->getFullPath());
        $finfo = new \finfo();
        $this->mimetype = $finfo->file($this->getFullPath(), FILEINFO_MIME_TYPE);
        return true;
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['path'];
        parent::setPreviousData(\array_merge($more, $fields));
    }
}
