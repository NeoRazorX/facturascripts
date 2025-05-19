<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\DocTransformation;

/**
 * Description of BusinessDocumentGenerator
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Rafael San José Tovar    <rafael.sanjose@x-netdigital.com>
 * @author Raúl Jiménez             <raljopa@gmail.com>
 */
class BusinessDocumentGenerator
{
    use ExtensionsTrait;

    /** @var array */
    protected $lastDocs = [];

    /** @var bool */
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
        $newDocClass = '\\FacturaScripts\\Dinamic\\Model\\' . $newClass;
        $newDoc = new $newDocClass();
        $fields = array_keys($newDoc->getModelFields());

        if (false === $this->pipeFalse('generateBefore', $prototype, $lines, $quantity, $properties, $newDoc)) {
            return false;
        }

        foreach (array_keys($prototype->getModelFields()) as $field) {
            // exclude properties not in new line
            if (false === in_array($field, $fields)) {
                continue;
            }

            // exclude some properties
            if (in_array($field, $prototype::dontCopyFields())) {
                continue;
            }

            // copy properties to new document
            $newDoc->{$field} = $prototype->{$field};
        }

        // assign the user
        $newDoc->nick = Session::user()->nick;

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

                $this->pipeFalse('generateTrue', $prototype, $lines, $quantity, $properties, $newDoc, $newLines);
                return true;
            }
        }

        if ($newDoc->exists()) {
            $newDoc->delete();
        }

        $this->pipeFalse('generateFalse', $prototype, $lines, $quantity, $properties, $newDoc);
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
        $fields = array_keys($newDoc->getNewLine()->getModelFields());

        foreach ($lines as $line) {
            // copy line properties to new line
            $arrayLine = [];
            foreach (array_keys($line->getModelFields()) as $field) {
                // exclude properties not in new line
                if (false === in_array($field, $fields)) {
                    continue;
                }

                // exclude some properties
                if (in_array($field, $line::dontCopyFields())) {
                    continue;
                }

                $arrayLine[$field] = $line->{$field};
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

        // copy related files
        if ($newDoc instanceof TransformerDocument) {
            $this->copyRelatedFiles($newDoc);
        }

        if (false === $this->pipeFalse('cloneLines', $prototype, $newDoc, $lines, $quantity)) {
            return false;
        }

        return true;
    }

    public function copyRelatedFiles(TransformerDocument $newDoc): bool
    {
        $relationModel = new AttachedFileRelation();
        foreach ($newDoc->parentDocuments() as $parent) {
            $whereDocs = [
                new DataBaseWhere('model', $parent->modelClassName()),
                new DataBaseWhere('modelid', $parent->primaryColumnValue())
            ];
            foreach ($relationModel->all($whereDocs, ['id' => 'ASC']) as $relation) {
                $newRelation = new AttachedFileRelation();
                $newRelation->idfile = $relation->idfile;
                $newRelation->model = $newDoc->modelClassName();
                $newRelation->modelid = $newDoc->primaryColumnValue();
                $newRelation->nick = $relation->nick;
                $newRelation->observations = $relation->observations;
                $newRelation->modelcode = $newDoc->codigo;
                if (false === $newRelation->save()) {
                    return false;
                }
            }
        }

        return true;
    }
}
