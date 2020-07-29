<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     *
     * @var bool
     */
    private static $documentGeneration = true;

    /**
     * Indicates whether the document can be modified
     *
     * @var bool
     */
    public $editable;

    /**
     *
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
     * Returns all children documents of this one.
     *
     * @return TransformerDocument[]
     */
    public function childrenDocuments()
    {
        $children = [];
        $keys = [];
        $docTransformation = new DocTransformation();
        $where = [
            new DataBaseWhere('model1', $this->modelClassName()),
            new DataBaseWhere('iddoc1', $this->primaryColumnValue())
        ];
        foreach ($docTransformation->all($where, [], 0, 0) as $docTrans) {
            /// we use this key to load documents only once
            $key = $docTrans->model2 . '|' . $docTrans->iddoc2;
            if (\in_array($key, $keys, true)) {
                continue;
            }

            $newModelClass = self::MODEL_NAMESPACE . $docTrans->model2;
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
    public function clear()
    {
        parent::clear();
        $this->editable = true;

        /// select default status
        foreach ($this->getAvaliableStatus() as $status) {
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
    public function delete()
    {
        if (\count($this->childrenDocuments()) > 0) {
            return false;
        }

        self::$dataBase->beginTransaction();

        /// remove lines to update stock
        foreach ($this->getLines() as $line) {
            if (false === $line->delete()) {
                self::$dataBase->rollback();
                return false;
            }
        }

        /// remove this model
        if (false === parent::delete()) {
            self::$dataBase->rollback();
            return false;
        }

        /// load parents and remove relations from DocTransformation
        $parents = $this->parentDocuments();
        $docTransformation = new DocTransformation();
        $docTransformation->deleteFrom($this->modelClassName(), $this->primaryColumnValue());

        /// change parent doc status
        foreach ($parents as $parent) {
            foreach ($parent->getAvaliableStatus() as $status) {
                if ($status->predeterminado) {
                    $parent->idestado = $status->idestado;
                    $parent->save();
                    break;
                }
            }
        }

        self::$dataBase->commit();
        return true;
    }

    /**
     * Returns all avaliable status for this type of document.
     *
     * @return EstadoDocumento[]
     */
    public function getAvaliableStatus()
    {
        if (!isset(self::$estados)) {
            $statusModel = new EstadoDocumento();
            self::$estados = $statusModel->all([], [], 0, 0);
        }

        $avaliables = [];
        foreach (self::$estados as $status) {
            if ($status->tipodoc === $this->modelClassName()) {
                $avaliables[] = $status;
            }
        }

        return $avaliables;
    }

    /**
     * Returns the EstadoDocumento model for this document.
     *
     * @return EstadoDocumento
     */
    public function getStatus()
    {
        $status = new EstadoDocumento();
        $status->loadFromCode($this->idestado);
        return $status;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new EstadoDocumento();

        return parent::install();
    }

    /**
     * Returns all parent document of this one.
     *
     * @return TransformerDocument[]
     */
    public function parentDocuments()
    {
        $parents = [];
        $keys = [];
        $docTransformation = new DocTransformation();
        $where = [
            new DataBaseWhere('model2', $this->modelClassName()),
            new DataBaseWhere('iddoc2', $this->primaryColumnValue())
        ];
        foreach ($docTransformation->all($where, [], 0, 0) as $docTrans) {
            /// we use this key to load documents only once
            $key = $docTrans->model1 . '|' . $docTrans->iddoc1;
            if (\in_array($key, $keys, true)) {
                continue;
            }

            $newModelClass = self::MODEL_NAMESPACE . $docTrans->model1;
            $newModel = new $newModelClass();
            if ($newModel->loadFromCode($docTrans->iddoc1)) {
                $parents[] = $newModel;
                $keys[] = $key;
            }
        }

        return $parents;
    }

    /**
     * Saves data in the database.
     * 
     * @return bool
     */
    public function save()
    {
        /// match editable with status
        $this->editable = $this->getStatus()->editable;
        return parent::save();
    }

    /**
     * 
     * @param bool $value
     */
    public function setDocumentGeneration($value)
    {
        self::$documentGeneration = $value;
    }

    /**
     * Check changed fields before updata the database.
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        if (!$this->editable && !$this->previousData['editable'] && $field != 'idestado') {
            $this->toolBox()->i18nLog()->warning('non-editable-document');
            return false;
        }

        if ($field === 'idestado') {
            $status = $this->getStatus();
            foreach ($this->getLines() as $line) {
                $line->actualizastock = $status->actualizastock;
                $line->save();
            }
            $docGenerator = new BusinessDocumentGenerator();
            if (!empty($status->generadoc) && self::$documentGeneration && !$docGenerator->generate($this, $status->generadoc)) {
                return false;
            }
        }

        return parent::onChange($field);
    }

    /**
     * Sets fields to be watched.
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['editable', 'idestado'];
        parent::setPreviousData(\array_merge($more, $fields));
    }
}
