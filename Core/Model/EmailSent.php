<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Email\NewMail;

/**
 * Model EmailSent
 *
 * @author Raul Jimenez         <raljopa@gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class EmailSent extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $addressee;

    /** @var bool */
    public $attachment;

    /** @var string */
    public $body;

    /** @var string */
    public $date;

    /** @var string */
    public $email_from;

    /** @var string */
    public $html;

    /** @var string */
    public $id;

    /** @var string */
    public $nick;

    /** @var bool */
    public $opened;

    /** @var string */
    public $subject;

    /** @var string */
    public $uuid;

    /** @var string */
    public $verificode;

    public function clear(): void
    {
        parent::clear();
        $this->date = Tools::dateTime();
        $this->opened = false;
    }

    public function getAttachments(): array
    {
        // leemos la carpeta de adjuntos
        $folderPath = NewMail::getAttachmentPath($this->email_from, 'Sent') . $this->uuid;
        if (false === is_dir(FS_FOLDER . '/' . $folderPath)) {
            return [];
        }

        // devolvemos los archivos
        $files = [];
        foreach (scandir(FS_FOLDER . '/' . $folderPath) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $filePath = $folderPath . '/' . $file;
            $files[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'path' => $filePath . '?myft=' . MyFilesToken::get($filePath, false),
            ];
        }

        return $files;
    }

    public function install(): string
    {
        // dependencias
        new User();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'emails_sent';
    }

    public function test(): bool
    {
        $body = Tools::noHtml($this->body);
        $this->body = strlen($body ?? '') > 5000 ? substr($body, 0, 4997) . '...' : $body;

        $this->html = Tools::noHtml($this->html);
        $this->subject = Tools::noHtml($this->subject);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ConfigEmail?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    public static function verify(string $verificode, string $addressee = ''): bool
    {
        $where = [Where::eq('verificode', $verificode)];
        if (!empty($addressee)) {
            $where[] = Where::eq('addressee', $addressee);
        }

        foreach (static::all($where) as $item) {
            $item->opened = true;
            $item->save();

            return true;
        }

        return false;
    }
}
