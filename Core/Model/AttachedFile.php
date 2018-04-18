<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018    Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

/**
 * Class to manage attached files.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class AttachedFile extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Contains the file name.
     *
     * @var string
     */
    public $filename;

    /**
     * Contains the relative path to file, from FS_MYFILES.
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
     * Timestamp with the upload date and time.
     *
     * @var string
     */
    public $uploadedat;

    /**
     * Content the mime content type.
     *
     * @var string
     */
    public $mimetype;

    /**
     * Contains the hash for the file.
     *
     * @var string
     */
    public $hash;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->filename = '';
        $this->path = '';
        $this->size = 0;
        $this->uploadedat = date('d-m-Y H:i:s');
        $this->mimetype = '';
        $this->hash = '';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
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
     * Remove the model data from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $status = parent::delete();
        if ($status) {
            @\unlink(\FS_FOLDER . $this->path);
        }

        return $status;
    }

    /**
     * Save the file to internal path, returns true if file was stored,false otherwhise
     *
     * @param string $receivedFile
     * @param string $fileName
     *
     * @return bool
     */
    public function saveFile($receivedFile, $fileName = null): bool
    {
        if (!\file_exists($receivedFile)) {
            self::$miniLog->critical(self::$i18n->trans('file-not-exists', ['%fileName%' => $receivedFile]));
            return false;
        }
        $cpFolder = FS_FOLDER . '/MyFiles/' . \date('Y') . '/' . \date('m') . '/';
        $destinyFolder = '/MyFiles/' . \date('Y') . '/' . \date('m') . '/';
        if (!\is_dir($cpFolder) && !mkdir($cpFolder, 0775, true) && !is_dir($cpFolder)) {
            self::$miniLog->critical(self::$i18n->trans('cant-create-folder', ['%folderName%' => $cpFolder]));
            return false;
        }
        $file = $fileName ?? bin2hex(random_bytes(32));
        if (!\file_exists($cpFolder . $file) && @copy($receivedFile, $cpFolder . $file)) {
            $this->size = filesize($receivedFile);
            $this->filename = $file;
            $this->path = $destinyFolder . $file;
            $this->mimetype = $this->getMimeType($cpFolder . $file);
            $this->hash = $this->getHash($cpFolder . $file);
            return true;
        }
        self::$miniLog->critical(self::$i18n->trans('file-yet-exists', ['%filePath%' => $cpFolder . $file]));
        return false;
    }

    /**
     * Returns the mime content type of the file.
     *
     * @param string $receivedFile
     *
     * @return string
     */
    public function getMimeType($receivedFile): string
    {
        $mime = 'application/octet-stream';

        if (\function_exists('mime_content_type')) {
            $mime = mime_content_type($receivedFile);
        } elseif (\class_exists('finfo')) {
            $finfo = new \finfo();
            $mime = $finfo->file($receivedFile, FILEINFO_MIME_TYPE);
        }

        return $mime;
    }

    /**
     * Return size un human readable format.
     *
     * @return string
     */
    public function getSize(): string
    {
        $bytes = $this->size;
        $decimals = 2;
        $sz = 'BKMGTP';
        $factor = (int) floor((\strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / (1024 ** $factor)) . ' ' . @$sz[$factor];
    }

    /**
     * Return the SHA1 hash for the file.
     *
     * @param string $receivedFile
     *
     * @return string
     */
    public function getHash($receivedFile): string
    {
        return sha1_file($receivedFile);
    }
}
