<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile as DinFile;

/**
 * Description of AttachedFileRelation
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class AttachedFileRelation extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * @var string
     */
    public $creationdate;

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $idfile;

    /**
     * @var string
     */
    public $model;

    /**
     * @var int
     */
    public $modelid;

    /**
     * @var string
     */
    public $modelcode;

    /**
     * @var string
     */
    public $nick;

    /**
     * @var string
     */
    public $observations;

    public function clear()
    {
        parent::clear();
        $this->creationdate = Tools::dateTime();
    }

    public function getFile(): DinFile
    {
        $file = new DinFile();
        $file->loadFromCode($this->idfile);
        return $file;
    }

    /**
     * Return the max file size that can be uploaded.
     *
     * @return int
     */
    public function getMaxFileUpload()
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    public function install(): string
    {
        // needed dependencies
        new DinFile();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'attached_files_rel';
    }

    public function test(): bool
    {
        $this->observations = Tools::noHtml($this->observations);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        if ($this->model) {
            $modelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $this->model;
            if (class_exists($modelClass)) {
                $model = new $modelClass();
                $code = empty($this->modelcode) ? $this->modelid : $this->modelcode;
                if ($model->loadFromCode($code)) {
                    return $model->url();
                }
            }
        }

        return parent::url($type, $list);
    }
}
