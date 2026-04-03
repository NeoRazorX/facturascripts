<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

/**
 * Documento transformable en otro tipo de documento.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class TransformerDocument extends BusinessDocument
{
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * @var bool
     */
    private static $document_generation = true;

    /**
     * Indica si el documento es editable.
     *
     * @var bool
     */
    public $editable;

    /**
     * @var EstadoDocumento[]
     */
    private static $estados;

    /**
     * Estado del documento, del modelo EstadoDocumento.
     *
     * @var int
     */
    public $idestado;

    /**
     * Estado anterior del documento, del modelo EstadoDocumento.
     *
     * @var int|null
     */
    public $idestado_ant;

    /**
     * Campos que se pueden modificar aunque el documento no sea editable.
     *
     * @var array
     */
    private static $unlocked_fields = ['femail', 'idestado', 'idestado_ant', 'numdocs', 'pagada'];

    /**
     * Añade un campo a la lista de campos desbloqueados (editables aunque el documento no sea editable).
     *
     * @param string $field
     */
    public static function addUnlockedField(string $field): void
    {
        if (false === in_array($field, self::$unlocked_fields, true)) {
            self::$unlocked_fields[] = $field;
        }
    }

    /**
     * Devuelve todos los documentos hijos de este.
     *
     * @return TransformerDocument[]
     */
    public function childrenDocuments(): array
    {
        $children = [];
        $keys = [];
        $where = [
            Where::eq('model1', $this->modelClassName()),
            Where::eq('iddoc1', $this->id())
        ];
        foreach (DocTransformation::all($where, [], 0, 0) as $docTrans) {
            // usamos esta clave para cargar documentos solo una vez
            $key = $docTrans->model2 . '|' . $docTrans->iddoc2;
            if (in_array($key, $keys, true)) {
                continue;
            }

            $newModelClass = self::MODEL_NAMESPACE . $docTrans->model2;
            if (false === class_exists($newModelClass)) {
                continue;
            }

            $newModel = new $newModelClass();
            if ($newModel->loadFromCode($docTrans->iddoc2)) {
                $children[] = $newModel;
                $keys[] = $key;
            }
        }

        return $children;
    }

    /**
     * Restablece los valores de todas las propiedades del modelo.
     */
    public function clear(): void
    {
        parent::clear();

        $this->editable = true;

        // seleccionamos el estado predeterminado
        foreach ($this->getAvailableStatus() as $status) {
            if ($status->predeterminado) {
                $this->idestado = $status->idestado;
                $this->editable = $status->editable;
                break;
            }
        }
    }

    /**
     * Elimina este documento de la base de datos.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (false === $this->exists()) {
            return true;
        }

        if (count($this->childrenDocuments()) > 0) {
            Tools::log()->warning('non-editable-document');
            return false;
        }

        // obtenemos las líneas antes de abrir la transacción para que la comprobación de tablas ocurra fuera
        $lines = $this->getLines();

        // comprobamos si ya hay una transacción abierta para no romperla
        $newTransaction = false === static::db()->inTransaction() && self::db()->beginTransaction();

        // eliminamos las líneas para actualizar el stock
        foreach ($lines as $line) {
            if ($line->delete()) {
                continue;
            }
            if ($newTransaction) {
                self::db()->rollback();
            }
            return false;
        }

        // eliminamos este modelo
        if (false === parent::delete()) {
            if ($newTransaction) {
                self::db()->rollback();
            }
            return false;
        }

        // eliminamos las relaciones y actualizamos la columna servido
        $parents = $this->parentDocuments();
        $docTransformation = new DocTransformation();
        $docTransformation->deleteFrom($this->modelClassName(), $this->id(), true);

        // cambiamos el estado del documento padre
        foreach ($parents as $parent) {
            $previousStatus = $parent->getPreviousStatus();
            if ($previousStatus && empty($previousStatus->generadoc)) {
                $parent->idestado = $previousStatus->idestado;
                $parent->save();
                continue;
            }

            foreach ($parent->getAvailableStatus() as $status) {
                if (!$status->predeterminado) {
                    continue;
                }

                $parent->idestado = $status->idestado;
                $parent->save();
                break;
            }
        }

        // añadimos el log de auditoría
        Tools::log($this->getAuditChannel())->warning('deleted-model', [
            '%model%' => $this->modelClassName(),
            '%key%' => $this->id(),
            '%desc%' => $this->primaryDescription(),
            'model-class' => $this->modelClassName(),
            'model-code' => $this->id(),
            'model-data' => $this->toArray()
        ]);

        if ($newTransaction) {
            self::db()->commit();
        }

        return true;
    }

    /**
     * Devuelve todos los estados disponibles para este tipo de documento.
     *
     * @return EstadoDocumento[]
     */
    public function getAvailableStatus(): array
    {
        if (null === self::$estados) {
            self::$estados = EstadoDocumento::all([], ['idestado' => 'ASC'], 0, 0);
        }

        $available = [];
        foreach (self::$estados as $status) {
            if ($status->tipodoc === $this->modelClassName()) {
                $available[] = $status;
            }
        }

        return $available;
    }

    /**
     * Devuelve el modelo EstadoDocumento de este documento.
     *
     * @return EstadoDocumento
     */
    public function getStatus(): EstadoDocumento
    {
        $status = new EstadoDocumento();
        $status->load($this->idestado);
        return $status;
    }

    /**
     * Devuelve el modelo EstadoDocumento anterior de este documento.
     *
     * @return EstadoDocumento|null
     */
    public function getPreviousStatus(): ?EstadoDocumento
    {
        if (empty($this->idestado_ant)) {
            return null;
        }

        $status = new EstadoDocumento();
        return $status->load($this->idestado_ant) ? $status : null;
    }

    /**
     * Devuelve la lista de campos desbloqueados (editables aunque el documento no sea editable).
     *
     * @return array
     */
    public static function getUnlockedFields(): array
    {
        return self::$unlocked_fields;
    }

    public function install(): string
    {
        // dependencias necesarias
        new EstadoDocumento();

        return parent::install();
    }

    /**
     * Devuelve todos los documentos padre de este.
     *
     * @return TransformerDocument[]
     */
    public function parentDocuments(): array
    {
        $parents = [];
        $keys = [];
        $where = [
            Where::eq('model2', $this->modelClassName()),
            Where::eq('iddoc2', $this->id())
        ];
        foreach (DocTransformation::all($where, [], 0, 0) as $docTrans) {
            // usamos esta clave para cargar documentos solo una vez
            $key = $docTrans->model1 . '|' . $docTrans->iddoc1;
            if (in_array($key, $keys, true)) {
                continue;
            }

            $newModelClass = self::MODEL_NAMESPACE . $docTrans->model1;
            if (false === class_exists($newModelClass)) {
                continue;
            }

            $newModel = new $newModelClass();
            if ($newModel->loadFromCode($docTrans->iddoc1)) {
                $parents[] = $newModel;
                $keys[] = $key;
            }
        }

        return $parents;
    }

    /**
     * Elimina un campo de la lista de campos desbloqueados.
     *
     * @param string $field
     */
    public static function removeUnlockedField(string $field): void
    {
        $key = array_search($field, self::$unlocked_fields, true);
        if ($key !== false) {
            unset(self::$unlocked_fields[$key]);
            self::$unlocked_fields = array_values(self::$unlocked_fields);
        }
    }

    /**
     * Guarda los datos en la base de datos.
     *
     * @return bool
     */
    public function save(): bool
    {
        // igualamos editable con el estado
        $this->editable = $this->getStatus()->editable;

        return parent::save();
    }

    public function setDocumentGeneration(bool $value): void
    {
        self::$document_generation = $value;
    }

    /**
     * Comprueba los campos modificados antes de actualizar la base de datos.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange(string $field): bool
    {
        // comprobamos si el campo está bloqueado cuando el documento no es editable
        if (!$this->editable && !$this->getOriginal('editable') && !in_array($field, self::$unlocked_fields, true)) {
            Tools::log()->warning('non-editable-document');
            return false;
        }

        if ($field !== 'idestado') {
            return parent::onChange($field);
        }

        // guardamos el estado anterior real antes del cambio
        $this->idestado_ant = $this->getOriginal('idestado');

        $status = $this->getStatus();
        if (empty($status->generadoc) || false === self::$document_generation) {
            // actualizamos las líneas para actualizar el stock
            foreach ($this->getLines() as $line) {
                $line->actualizastock = $status->actualizastock;
                $line->save();
            }
            // no generamos un nuevo documento
            return parent::onChange($field);
        }

        // actualizamos las líneas para actualizar el stock y desglosar cantidades
        $newLines = [];
        $quantities = [];
        foreach ($this->getLines() as $line) {
            if ($line->servido < $line->cantidad) {
                $quantities[$line->primaryColumnValue()] = $line->cantidad - $line->servido;
                $newLines[] = $line;
            }

            $line->actualizastock = $status->actualizastock;
            $line->servido = $line->cantidad;
            $line->save();
        }

        // generamos el nuevo documento, cuando no hay hijos
        $generator = new BusinessDocumentGenerator();
        if (empty($this->childrenDocuments())) {
            return $generator->generate($this, $status->generadoc) && parent::onChange($field);
        }

        // generamos el nuevo documento, cuando hay hijos
        if ($newLines) {
            return $generator->generate($this, $status->generadoc, $newLines, $quantities) && parent::onChange($field);
        }

        // no hay líneas pendientes para generar un nuevo documento
        return true;
    }
}
