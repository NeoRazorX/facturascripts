<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 * Copyright (C) 2017  Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
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
 * Un estado asociado a los documentos para distinguirlos por grupos.
 * Por ejemplo: Pendientes, Aprobados, ...
 *
 * @author Francesc Pineda Segarra <francesc.pìneda.segarra@gmail.com>
 */
class EstadoDocumento
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $id;

    /**
     * Tipo de documento.
     *
     * @var string
     */
    public $documento;

    /**
     * Número de estado.
     *
     * @var int
     */
    public $status;

    /**
     * Nombre del estado para mostrar al usuario.
     *
     * @var string
     */
    public $nombre;

    /**
     * Si el estado está o no bloqueado
     *
     * @var bool
     */
    public $bloquedo;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'estados_documentos';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->id = null;
        $this->documento = null;
        $this->status = null;
        $this->nombre = null;
        $this->bloqueado = null;
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $status = FALSE;

        $this->documento = self::noHtml($this->documento);
        $this->nombre = self::noHtml($this->nombre);

        if (strlen($this->documento) < 1 || strlen($this->documento) > 20) {
            $this->miniLog->alert($this->i18n->trans('document-type-valid-length'));
        } elseif (strlen($this->nombre) < 1 || strlen($this->nombre) > 20) {
            $this->miniLog->alert($this->i18n->trans('status-name-valid-length'));
        } elseif (!is_numeric($this->status)) {
            $this->miniLog->alert($this->i18n->trans('status-value-is-number'));
        } else {
            $status = TRUE;
        }

        return $status;
    }

    /**
     * Devuelve una array con los estados para el tipo de documento indicado
     *
     * @param $doc
     *
     * @return array
     */
    public function getByDocument($doc)
    {
        $list = [];

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE documento = ' . $this->dataBase->var2str($doc) . ' ORDER BY id ASC;';
        $data = $this->dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $list[] = new self($d);
            }
        }

        return $list;
    }

    /**
     * Devuelve un array con todas los estados
     *
     * @return array
     */
    public function allDocumentTypes()
    {
        $list = [];

        $sql = 'SELECT DISTINCT(documento) FROM ' . $this->tableName() . ' ORDER BY id ASC;';
        $data = $this->dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $list[] = $d['documento'];
            }
        }

        return $list;
    }
}
