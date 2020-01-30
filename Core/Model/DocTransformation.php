<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;

/**
 * A model to manage the transformations of documents. For example aprobe order to delibery note.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Rafael San José Tovar        <rafael.sanjose@x-netdigital.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class DocTransformation extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var float
     */
    public $cantidad;

    /**
     * Primary key. Autoincremental.
     *
     * @var int
     */
    public $id;

    /**
     * id of document 1
     *
     * @var int
     */
    public $iddoc1;

    /**
     * id of document 2
     *
     * @var int
     */
    public $iddoc2;

    /**
     * id of the line in document 1
     *
     * @var int
     */
    public $idlinea1;

    /**
     * id of the line in document 2
     *
     * @var int
     */
    public $idlinea2;

    /**
     * Name of model1. Varchar(30)
     *
     * @var string
     */
    public $model1;

    /**
     * Name of model2. Varchar(30)
     *
     * @var string
     */
    public $model2;

    public function clear()
    {
        parent::clear();
        $this->cantidad = 0.0;
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        /// update served quantity in parent line
        $line = $this->getParentLine();
        if ($line->exists() && isset($line->servido)) {
            $line->servido -= $this->cantidad;
            $line->save();
        }

        return parent::delete();
    }

    /**
     * Removes related data from this document.
     * 
     * @param string $tipoDoc
     * @param int    $idDoc
     */
    public function deleteFrom($tipoDoc, $idDoc)
    {
        $where = [
            new DataBaseWhere('model1', $tipoDoc),
            new DataBaseWhere('iddoc1', $idDoc),
        ];
        foreach ($this->all($where, [], 0, 0) as $line) {
            $line->delete();
        }

        $where2 = [
            new DataBaseWhere('model2', $tipoDoc),
            new DataBaseWhere('iddoc2', $idDoc),
        ];
        foreach ($this->all($where2, [], 0, 0) as $line) {
            $line->delete();
        }
    }

    /**
     * 
     * @return BusinessDocumentLine
     */
    public function getParentLine()
    {
        $modelClass = '\\FacturaScripts\\Dinamic\\Model\\Linea' . $this->model1;
        if (class_exists($modelClass)) {
            $line = new $modelClass();
            $line->loadFromCode($this->idlinea1);
            return $line;
        }

        return new LineaAlbaranCliente();
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
        return 'doctransformations';
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            /// update served quantity in parent line
            $line = $this->getParentLine();
            if ($line->exists() && isset($line->servido)) {
                $line->servido += $this->cantidad;
                $line->save();
            }

            return true;
        }

        return false;
    }
}
