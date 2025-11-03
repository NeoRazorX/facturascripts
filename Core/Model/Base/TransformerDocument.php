<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\LogMessage;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

/**
 * Description of TransformerDocument
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
     * Indicates whether the document can be modified
     *
     * @var bool
     */
    public $editable;

    /**
     * @var EstadoDocumento[]
     */
    private static $estados;

    /**
     * Document status, from EstadoDocumento model.
     *
     * @var int
     */
    public $idestado;

    /**
     * Campos que se pueden modificar aunque el documento no sea editable.
     *
     * @var array
     */
    private static $unlocked_fields = ['femail', 'idestado', 'numdocs', 'pagada'];

    /**
     * Adds a field to the list of unlocked fields (editable even when document is not editable).
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
     * Returns all children documents of this one.
     *
     * @return TransformerDocument[]
     */
    public function childrenDocuments(): array
    {
        $children = [];
        $keys = [];
        $where = [
            new DataBaseWhere('model1', $this->modelClassName()),
            new DataBaseWhere('iddoc1', $this->id())
        ];
        foreach (DocTransformation::all($where, [], 0, 0) as $docTrans) {
            // we use this key to load documents only once
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
     * Reset the values of all model properties.
     */
    public function clear(): void
    {
        parent::clear();

        $this->editable = true;

        // select default status
        foreach ($this->getAvailableStatus() as $status) {
            if ($status->predeterminado) {
                $this->idestado = $status->idestado;
                $this->editable = $status->editable;
                break;
            }
        }
    }

    /**
     * Removes this document from the database.
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

        // we check if there is already an open transaction so as not to break it
        $newTransaction = false === static::db()->inTransaction() && self::db()->beginTransaction();

        // remove lines to update stock
        foreach ($this->getLines() as $line) {
            if ($line->delete()) {
                continue;
            }
            if ($newTransaction) {
                self::db()->rollback();
            }
            return false;
        }

        // remove this model
        if (false === parent::delete()) {
            if ($newTransaction) {
                self::db()->rollback();
            }
            return false;
        }

        // remove relations and update servido column
        $parents = $this->parentDocuments();
        $docTransformation = new DocTransformation();
        $docTransformation->deleteFrom($this->modelClassName(), $this->id(), true);

        // change parent doc status
        foreach ($parents as $parent) {
            foreach ($parent->getAvailableStatus() as $status) {
                if ($status->predeterminado) {
                    $parent->idestado = $status->idestado;
                    $parent->save();
                    break;
                }
            }
        }

        // add audit log
        Tools::log(LogMessage::AUDIT_CHANNEL)->warning('deleted-model', [
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
     * Returns all available status for this type of document.
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
     * Returns the EstadoDocumento model for this document.
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
     * Returns the list of unlocked fields (editable even when document is not editable).
     *
     * @return array
     */
    public static function getUnlockedFields(): array
    {
        return self::$unlocked_fields;
    }

    public function install(): string
    {
        // needed dependencies
        new EstadoDocumento();

        return parent::install();
    }

    /**
     * Returns all parent document of this one.
     *
     * @return TransformerDocument[]
     */
    public function parentDocuments(): array
    {
        $parents = [];
        $keys = [];
        $where = [
            new DataBaseWhere('model2', $this->modelClassName()),
            new DataBaseWhere('iddoc2', $this->id())
        ];
        foreach (DocTransformation::all($where, [], 0, 0) as $docTrans) {
            // we use this key to load documents only once
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
     * Removes a field from the list of unlocked fields.
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
     * Saves data in the database.
     *
     * @return bool
     */
    public function save(): bool
    {
        // match editable with status
        $this->editable = $this->getStatus()->editable;

        return parent::save();
    }

    public function setDocumentGeneration(bool $value): void
    {
        self::$document_generation = $value;
    }

    /**
     * Check changed fields before update the database.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange(string $field): bool
    {
        // check if field is locked when document is not editable
        if (!$this->editable && !$this->getOriginal('editable') && !in_array($field, self::$unlocked_fields, true)) {
            Tools::log()->warning('non-editable-document');
            return false;
        }

        if ($field !== 'idestado') {
            return parent::onChange($field);
        }

        $status = $this->getStatus();
        if (empty($status->generadoc) || false === self::$document_generation) {
            // update lines to update stock
            foreach ($this->getLines() as $line) {
                $line->actualizastock = $status->actualizastock;
                $line->save();
            }
            // do not generate a new document
            return parent::onChange($field);
        }

        // update lines to update stock and break down quantities
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

        // generate the new document, when there are no children
        $generator = new BusinessDocumentGenerator();
        if (empty($this->childrenDocuments())) {
            return $generator->generate($this, $status->generadoc) && parent::onChange($field);
        }

        // generate the new document, when there are children
        if ($newLines) {
            return $generator->generate($this, $status->generadoc, $newLines, $quantities) && parent::onChange($field);
        }

        // no pending lines to generate a new document
        return true;
    }
}
