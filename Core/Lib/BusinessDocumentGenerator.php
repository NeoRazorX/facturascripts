<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\DocTransformation;

/**
 * Generador de documentos de negocio.
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
     * Genera un nuevo documento a partir de un documento prototipo.
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

        foreach (array_keys($prototype->getModelFields()) as $field) {
            // excluimos las propiedades que no existen en el nuevo documento
            if (false === in_array($field, $fields)) {
                continue;
            }

            // excluimos algunas propiedades
            if (in_array($field, $prototype::dontCopyFields())) {
                continue;
            }

            // copiamos las propiedades al nuevo documento
            $newDoc->{$field} = $prototype->{$field};
        }

        // asignamos el usuario
        $newDoc->nick = Session::user()->nick;

        if (self::$sameDate) {
            $newDoc->fecha = $prototype->fecha;
            $newDoc->hora = $prototype->hora;
        }

        foreach ($properties as $key => $value) {
            $newDoc->{$key} = $value;
        }

        if (false === $this->pipeFalse('generateBefore', $prototype, $lines, $quantity, $properties, $newDoc)) {
            return false;
        }

        $protoLines = empty($lines) ? $prototype->getLines() : $lines;
        if ($newDoc->save() && $this->cloneLines($prototype, $newDoc, $protoLines, $quantity)) {
            // recalculamos los totales del nuevo documento
            $newLines = $newDoc->getLines();
            if (Calculator::calculate($newDoc, $newLines, true)) {
                // añadimos el documento a la lista de últimos documentos
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
     * Clona las líneas del documento prototipo en el nuevo documento.
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
            // copiamos las propiedades de la línea a la nueva línea
            $arrayLine = [];
            foreach (array_keys($line->getModelFields()) as $field) {
                // excluimos las propiedades que no existen en la nueva línea
                if (false === in_array($field, $fields)) {
                    continue;
                }

                // excluimos algunas propiedades
                if (in_array($field, $line::dontCopyFields())) {
                    continue;
                }

                $arrayLine[$field] = $line->{$field};
            }

            if (isset($quantity[$line->id()])) {
                $arrayLine['cantidad'] = $quantity[$line->id()];
            }

            if (empty($arrayLine['cantidad']) && !empty($line->cantidad)) {
                continue;
            }

            // actualizamos el servido de la línea original antes de guardar la nueva línea,
            // para que la parte de stock que deja de cubrir la pueda tomar la nueva línea
            if (!empty($line->id())) {
                $line->reload();
                $line->servido += (float)$arrayLine['cantidad'];
                if (!$line->save()) {
                    return false;
                }
            }

            $newLine = $newDoc->getNewLine($arrayLine);
            if (!$newLine->save()) {
                return false;
            }

            // guardamos la relación
            $docTrans->clear();
            $docTrans->cantidad = $newLine->cantidad;
            $docTrans->model1 = $prototype->modelClassName();
            $docTrans->iddoc1 = $line->documentColumnValue();
            $docTrans->idlinea1 = $line->id();
            $docTrans->model2 = $newDoc->modelClassName();
            $docTrans->iddoc2 = $newDoc->id();
            $docTrans->idlinea2 = $newLine->id();
            if (!empty($line->id()) && !$docTrans->save()) {
                return false;
            }

            if (false === $this->pipeFalse('cloneLine', $prototype, $line, $newLine->cantidad, $newDoc, $newLine)) {
                return false;
            }
        }

        // copiamos los archivos relacionados
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
                Where::eq('model', $parent->modelClassName()),
                Where::eq('modelid', $parent->id())
            ];
            foreach ($relationModel->all($whereDocs, ['id' => 'ASC']) as $relation) {
                $newRelation = new AttachedFileRelation();
                $newRelation->idfile = $relation->idfile;
                $newRelation->model = $newDoc->modelClassName();
                $newRelation->modelid = $newDoc->id();
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
