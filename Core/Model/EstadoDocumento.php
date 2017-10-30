<?php
/**
 * This file is part of FacturaScripts
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
    public function tableName()
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
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
        $estados = [
            ['documento' => 'ventas_presupuesto', 'status' => 0, 'nombre' => 'Pendiente', 'bloqueado' => true],
            ['documento' => 'ventas_presupuesto', 'status' => 1, 'nombre' => 'Aprobado', 'bloqueado' => true],
            ['documento' => 'ventas_presupuesto', 'status' => 2, 'nombre' => 'Rechazado', 'bloqueado' => true],
            ['documento' => 'ventas_pedido', 'status' => 0, 'nombre' => 'Pendiente', 'bloqueado' => true],
            ['documento' => 'ventas_pedido', 'status' => 1, 'nombre' => 'Aprobado', 'bloqueado' => true],
            ['documento' => 'ventas_pedido', 'status' => 2, 'nombre' => 'Rechazado', 'bloqueado' => true],
            ['documento' => 'ventas_pedido', 'status' => 3, 'nombre' => 'En trámite', 'bloqueado' => false],
            ['documento' => 'ventas_pedido', 'status' => 4, 'nombre' => 'Back orders', 'bloqueado' => false]
        ];
        $sql = '';
        foreach ($estados as $pos => $estado) {
            $sql .= 'INSERT INTO ' . $this->tableName()
                . ' (id, documento, status, nombre, bloqueado)'
                . ' VALUES ('
                . $this->var2str($pos + 1)
                . ',' . $this->var2str($estado['documento'])
                . ',' . $this->var2str($estado['status'])
                . ',' . $this->var2str($estado['nombre'])
                . ',' . $this->var2str($estado['bloqueado'])
                . ');';
        }

        return $sql;
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
            . ' WHERE documento = ' . $this->var2str($doc) . ' ORDER BY id ASC;';
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
