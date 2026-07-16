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

    /** Dirección de correo electrónico del destinatario. @var string */
    public $addressee;

    /** Indica si el correo contiene archivos adjuntos. @var bool */
    public $attachment;

    /** Cuerpo del correo en texto sin formato. @var string */
    public $body;

    /** Fecha y hora de envío del correo. @var string */
    public $date;

    /** Dirección de correo electrónico del remitente. @var string */
    public $email_from;

    /** Cuerpo del correo en formato HTML. @var string */
    public $html;

    /** Identificador único del correo enviado. @var string */
    public $id;

    /** Nombre del usuario que envió el correo. @var string */
    public $nick;

    /** Indica si el destinatario ha abierto o verificado el correo. @var bool */
    public $opened;

    /** Asunto del correo enviado. @var string */
    public $subject;

    /** Identificador utilizado para agrupar el correo y sus archivos adjuntos. @var string */
    public $uuid;

    /** Código utilizado para verificar la interacción del destinatario. @var string */
    public $verificode;

    public function clear(): void
    {
        parent::clear();
        $this->date = Tools::dateTime();
        $this->opened = false;
    }

    public function delete(): bool
    {
        // eliminamos los archivos adjuntos si existen
        if ($this->attachment && !empty($this->uuid) && !empty($this->email_from)) {
            $folderPath = NewMail::getAttachmentPath($this->email_from, 'Sent') . $this->uuid;
            $fullPath = FS_FOLDER . '/' . $folderPath;

            if (is_dir($fullPath)) {
                // eliminar todos los archivos del directorio
                foreach (scandir($fullPath) as $file) {
                    if ('.' === $file || '..' === $file) {
                        continue;
                    }

                    $filePath = $fullPath . '/' . $file;
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }

                // eliminar el directorio
                rmdir($fullPath);
            }
        }

        return parent::delete();
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
        $this->body = mb_strlen($body ?? '', 'UTF-8') > 5000 ? mb_substr($body, 0, 4997, 'UTF-8') . '...' : $body;

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
