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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Where;

/**
 * A model to manage the transformations of documents. For example aprove order to delivery note.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Rafael San José Tovar        <rafael.sanjose@x-netdigital.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class DocTransformation extends ModelClass
{
    use ModelTrait;

    /** Cantidad transformada entre las dos líneas de documento. @var float */
    public $cantidad;

    /** Identificador único de la transformación. @var int */
    public $id;

    /** Identificador del documento de origen. @var int */
    public $iddoc1;

    /** Identificador del documento de destino. @var int */
    public $iddoc2;

    /** Identificador de la línea del documento de origen. @var int */
    public $idlinea1;

    /** Identificador de la línea del documento de destino. @var int */
    public $idlinea2;

    /** Nombre del modelo del documento de origen. @var string */
    public $model1;

    /** Nombre del modelo del documento de destino. @var string */
    public $model2;

    public function clear(): void
    {
        parent::clear();
        $this->cantidad = 0.0;
    }

    /**
     * Removes related data from this document.
     *
     * @param string $tipoDoc
     * @param int $idDoc
     */
    public function deleteFrom(string $tipoDoc, int $idDoc): void
    {
        $options = [
            [Where::eq('model1', $tipoDoc), Where::eq('iddoc1', $idDoc)],
            [Where::eq('model2', $tipoDoc), Where::eq('iddoc2', $idDoc)]
        ];
        foreach ($options as $where) {
            foreach ($this->all($where, [], 0, 0) as $line) {
                $line->delete();
            }
        }
    }

    /**
     * @return BusinessDocumentLine
     */
    public function getParentLine(): BusinessDocumentLine
    {
        $modelClass = '\\FacturaScripts\\Dinamic\\Model\\Linea' . $this->model1;
        if (class_exists($modelClass)) {
            $line = new $modelClass();
            $line->load($this->idlinea1);
            return $line;
        }

        return new LineaAlbaranCliente();
    }

    /**
     * @return BusinessDocumentLine
     */
    public function getChildLine(): BusinessDocumentLine
    {
        $modelClass = '\\FacturaScripts\\Dinamic\\Model\\Linea' . $this->model2;
        if (class_exists($modelClass)) {
            $line = new $modelClass();
            $line->load($this->idlinea2);
            return $line;
        }

        return new LineaAlbaranCliente();
    }

    public static function tableName(): string
    {
        return 'doctransformations';
    }

    protected function onDelete(): void
    {
        // restamos la cantidad al servido de la línea del documento padre
        if ($this->cantidad) {
            $parentLine = $this->getParentLine();
            if ($parentLine->exists()) {
                $parentLine->servido -= $this->cantidad;
                $parentLine->save();
            }
        }

        parent::onDelete();
    }
}
