<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Dinamic\Model\DocTransformation;

/**
 * Description of BusinessDocumentGenerator
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Rafael San José Tovar    <rafael.sanjose@x-netdigital.com>
 */
class BusinessDocumentGenerator
{

    /**
     * Exclude fields
     *
     * @var array
     */
    public $excludeFields = [
        'codejercicio', 'codigo', 'fecha', 'femail',
        'hora', 'idestado', 'neto', 'netosindto', 'numero', 'total',
        'totalirpf', 'totaliva', 'totalrecargo',
    ];

    /**
     *
     * @var array
     */
    protected $lastDocs = [];

    /**
     * Generates a new document from a prototype document.
     *
     * @param BusinessDocument $prototype
     * @param string           $newClass
     * @param array            $lines
     * @param array            $quantity
     * @param array            $properties
     *
     * @return bool
     */
    public function generate(BusinessDocument $prototype, string $newClass, $lines = [], $quantity = [], $properties = [])
    {
        // Add primary column to exclude fields
        $this->excludeFields[] = $prototype->primaryColumn();

        $newDocClass = '\\FacturaScripts\\Dinamic\\Model\\' . $newClass;
        $newDoc = new $newDocClass();
        foreach (array_keys($prototype->getModelFields()) as $field) {
            /// exclude some properties
            if (in_array($field, $this->excludeFields)) {
                continue;
            }

            /// copy properties to new document
            $newDoc->{$field} = $prototype->{$field};
        }

        foreach ($properties as $key => $value) {
            $newDoc->{$key} = $value;
        }

        $protoLines = empty($lines) ? $prototype->getLines() : $lines;
        if ($newDoc->save() && $this->cloneLines($prototype, $newDoc, $protoLines, $quantity)) {
            /// recalculate totals on new document
            $tool = new BusinessDocumentTools();
            $tool->recalculate($newDoc);
            $newDoc->save();

            /// add to last doc list
            $this->lastDocs[] = $newDoc;
            return true;
        }

        if ($newDoc->exists()) {
            $newDoc->delete();
        }

        return false;
    }

    /**
     * 
     * @return array
     */
    public function getLastDocs()
    {
        return $this->lastDocs;
    }

    /**
     * Clone the lines from the prototype document, to new document.
     *
     * @param BusinessDocument       $prototype
     * @param BusinessDocument       $newDoc
     * @param BusinessDocumentLine[] $lines
     * @param array                  $quantity
     *
     * @return bool
     */
    protected function cloneLines(BusinessDocument $prototype, BusinessDocument $newDoc, $lines, $quantity)
    {
        $docTrans = new DocTransformation();
        foreach ($lines as $line) {
            /// copy line properties to new line
            $arrayLine = [];
            foreach (array_keys($line->getModelFields()) as $field) {
                if ($field !== 'idlinea') {
                    $arrayLine[$field] = $line->{$field};
                }
            }

            if (isset($quantity[$line->primaryColumnValue()])) {
                $arrayLine['cantidad'] = $quantity[$line->primaryColumnValue()];
            }

            if ($arrayLine['cantidad'] == 0 && $line->cantidad != 0) {
                continue;
            }

            $newLine = $newDoc->getNewLine($arrayLine);
            if (!$newLine->save()) {
                return false;
            }

            /// save relation
            $docTrans->clear();
            $docTrans->cantidad = $newLine->cantidad;
            $docTrans->model1 = $prototype->modelClassName();
            $docTrans->iddoc1 = $line->documentColumnValue();
            $docTrans->idlinea1 = $line->primaryColumnValue();
            $docTrans->model2 = $newDoc->modelClassName();
            $docTrans->iddoc2 = $newDoc->primaryColumnValue();
            $docTrans->idlinea2 = $newLine->primaryColumnValue();
            if (!$docTrans->save()) {
                return false;
            }
        }

        return true;
    }
}
