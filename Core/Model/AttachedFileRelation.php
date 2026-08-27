<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile as DinFile;

/**
 * Relación entre un archivo adjunto y el registro de cualquier otro modelo. Se guarda en la
 * tabla `attached_files_rel` y permite asociar un mismo `AttachedFile` (`idfile`) a varios
 * documentos, clientes, productos, etc., sin necesidad de añadir campos a esas tablas.
 *
 * El registro relacionado se identifica por el nombre del modelo (`model`) junto con
 * `modelid` cuando su clave primaria es numérica o `modelcode` cuando es un código. Además
 * se guarda quién creó la relación, la fecha, unas observaciones y un campo `orden` para
 * poder ordenar los adjuntos de un mismo registro.
 *
 * El método `url()` resuelve dinámicamente la clase del modelo en `Dinamic` para devolver
 * la url del registro al que pertenece el archivo.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class AttachedFileRelation extends ModelClass
{
    use ModelTrait;

    /** @var string Fecha y hora de creación de la relación. */
    public $creationdate;

    /** @var int Identificador único de la relación. */
    public $id;

    /** @var int Identificador del archivo adjunto relacionado. */
    public $idfile;

    /** @var string Nombre del modelo al que pertenece el archivo. */
    public $model;

    /** @var int Identificador numérico del registro relacionado. */
    public $modelid;

    /** @var string Código del registro relacionado cuando su clave no es numérica. */
    public $modelcode;

    /** @var string Nombre del usuario que creó la relación. */
    public $nick;

    /** @var string Observaciones sobre el archivo adjunto. */
    public $observations;

    /** @var int Posición utilizada para ordenar los archivos relacionados. */
    public $orden;

    public function clear(): void
    {
        parent::clear();
        $this->creationdate = Tools::dateTime();

        // Inicialmente el orden es el id
        // hasta que se asigne un orden en concreto.
        $this->orden = $this->orden ?? $this->id;
    }

    public function getFile(): ?DinFile
    {
        return $this->belongsTo(AttachedFile::class, 'idfile');
    }

    /**
     * Return the max file size that can be uploaded.
     *
     * @return float
     */
    public function getMaxFileUpload(): float
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    public function install(): string
    {
        // needed dependencies
        new DinFile();

        return parent::install();
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
