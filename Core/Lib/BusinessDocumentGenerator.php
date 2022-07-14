<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Base\ExtensionsTrait;
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

    use ExtensionsTrait;

    /**
     * Document fields to exclude.
     *
     * @var array
     */
    public $excludeFields = [
        'codejercicio', 'codigo', 'codigorect', 'fecha', 'femail', 'hora', 'idasiento', 'idestado', 'idfacturarect',
        'neto', 'netosindto', 'numero', 'pagada', 'total', 'totalirpf', 'totaliva', 'totalrecargo', 'totalsuplidos'];

    /**
     * Line fields to exclude.
     *
     * @var array
     */
    public $excludeLineFields = ['idlinea', 'orden', 'servido'];

    /**
     * @var array
     */
    protected $lastDocs = [];

    /**
     * @var bool
     */
    private static $sameDate = false;

    /**
     * Generates a new document from a prototype document.
     *
     * @param BusinessDocument $prototype
     * @param string $newClass
     * @param array $lines
     * @param array $quantity
     * @param array $properties
     *
     * @return bool
     */
    public function generate(BusinessDocument $prototype, string $newClass, array $lines = [], array $quantity = [], array $properties = []): bool
    {
        // Add primary column to exclude fields
        $this->excludeFields[] = $prototype->primaryColumn();

        $newDocClass = '\\FacturaScripts\\Dinamic\\Model\\' . $newClass;
        $newDoc = new $newDocClass();
        foreach (array_keys($prototype->getModelFields()) as $field) {
            // exclude some properties
            if (in_array($field, $this->excludeFields)) {
                continue;
            }

            // copy properties to new document
            $newDoc->{$field} = $prototype->{$field};
        }

        if (self::$sameDate) {
            $newDoc->fecha = $prototype->fecha;
            $newDoc->hora = $prototype->hora;
        }

        foreach ($properties as $key => $value) {
            $newDoc->{$key} = $value;
        }

        $protoLines = empty($lines) ? $prototype->getLines() : $lines;
        if ($newDoc->save() && $this->cloneLines($prototype, $newDoc, $protoLines, $quantity)) {
            // recalculate totals on new document
            $newLines = $newDoc->getLines();
            if (Calculator::calculate($newDoc, $newLines, true)) {
                // add to last doc list
                $this->lastDocs[] = $newDoc;
                return true;
            }
        }

        if ($newDoc->exists()) {
            $newDoc->delete();
        }

        return false;
    }

    /**
     * @return BusinessDocument[]
     */
    public function getLastDocs(): array
    {
        return $this->lastDocs;
    }

    public static function setSameDate(bool $value)
    {
        self::$sameDate = $value;
    }

    /**
     * Clone the lines from the prototype document, to new document.
     *
     * @param BusinessDocument $prototype
     * @param BusinessDocument $newDoc
     * @param BusinessDocumentLine[] $lines
     * @param array $quantity
     *
     * @return bool
     */
    protected function cloneLines(BusinessDocument $prototype, BusinessDocument $newDoc, array $lines, array $quantity): bool
    {
        $docTrans = new DocTransformation();
        foreach ($lines as $line) {
            // copy line properties to new line
            $arrayLine = [];
            foreach (array_keys($line->getModelFields()) as $field) {
                if (in_array($field, $this->excludeLineFields) === false) {
                    $arrayLine[$field] = $line->{$field};
                }
            }

            if (isset($quantity[$line->primaryColumnValue()])) {
                $arrayLine['cantidad'] = $quantity[$line->primaryColumnValue()];
            }

            if (empty($arrayLine['cantidad']) && !empty($line->cantidad)) {
                continue;
            }

            $newLine = $newDoc->getNewLine($arrayLine);
            if (!$newLine->save()) {
                return false;
            }

            // save relation
            $docTrans->clear();
            $docTrans->cantidad = $newLine->cantidad;
            $docTrans->model1 = $prototype->modelClassName();
            $docTrans->iddoc1 = $line->documentColumnValue();
            $docTrans->idlinea1 = $line->primaryColumnValue();
            $docTrans->model2 = $newDoc->modelClassName();
            $docTrans->iddoc2 = $newDoc->primaryColumnValue();
            $docTrans->idlinea2 = $newLine->primaryColumnValue();
            if (!empty($line->primaryColumnValue()) && !$docTrans->save()) {
                return false;
            }

            if (false === $this->pipeFalse('cloneLine', $prototype, $line, $newLine->cantidad, $newDoc, $newLine)) {
                return false;
            }
        }

        if (false === $this->pipeFalse('cloneLines', $prototype, $newDoc, $lines, $quantity)) {
            return false;
        }

        return true;
    }
}
