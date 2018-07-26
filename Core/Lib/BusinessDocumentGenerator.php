<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model;
use FacturaScripts\Core\Model\Base\BusinessDocument;

/**
 * Description of BusinessDocumentGenerator
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Rafael San José Tovar <rafael.sanjose@x-netdigital.com>
 */
class BusinessDocumentGenerator
{

    /**
     * New document generated.
     *
     * @var mixed
     */
    private $newDoc;

    /**
     * Generates a new document from a prototype document.
     *
     * @param BusinessDocument $prototype
     * @param string           $newClass
     * @param array            $auxData
     *
     * @return bool
     */
    public function generate(BusinessDocument $prototype, string $newClass, $auxData = [])
    {
        $exclude = ['codigo', 'idestado', 'fecha', 'hora', 'numero', 'femail'];
        $newDocClass = '\\FacturaScripts\\Dinamic\\Model\\' . $newClass;
        $this->newDoc = new $newDocClass();
        foreach (array_keys($prototype->getModelFields()) as $field) {
            if (in_array($field, $exclude) || !property_exists($newDocClass, $field)) {
                continue;
            }

            $this->newDoc->{$field} = $prototype->{$field};
        }

        if ($this->newDoc->save() && $this->cloneLines($prototype, $this->newDoc, $auxData)) {
            return true;
        }

        return false;
    }

    /**
     * Clone the lines from the prototype document, to previous generated document.
     * Requires a previous call to generate method.
     *
     * @param BusinessDocument $prototype
     * @param array            $auxData
     *
     * @return bool
     */
    public function addLinesFrom(BusinessDocument $prototype, $auxData = [])
    {
        if ($this->newDoc === null) {
            return false;
        }

        return $this->cloneLines($prototype, $this->newDoc, $auxData);
    }

    /**
     * Return the new generated doc.
     *
     * @return mixed
     */
    public function getNewDoc()
    {
        return $this->newDoc;
    }

    /**
     * Clone the lines from the prototype document, to new document.
     *
     * @param BusinessDocument $prototype
     * @param mixed            $newDoc
     * @param array            $auxData
     *
     * @return bool
     */
    private function cloneLines(BusinessDocument $prototype, $newDoc, $auxData = [])
    {
        $sameType = \get_class($prototype) === \get_class($newDoc);
        $docTrans = new \FacturaScripts\Dinamic\Model\DocTransformation();

        // start transaction
        $this->dataBase->beginTransaction();

        // main save process
        try {
            foreach ($prototype->getLines() as $line) {
                $docTrans->clear();

                $arrayLine = [];
                foreach ($line->getModelFields() as $field => $value) {
                    $arrayLine[$field] = $line->{$field};
                    /// Remove idlinea if are different document types
                    if (!$sameType && $field === 'idlinea') {
                        unset($arrayLine[$field]);
                    }
                }

                /// Fix quantity value if needed
                if (!empty($auxData) && isset($auxData[$line->idlinea])) {
                    $arrayLine['cantidad'] = $auxData[$line->idlinea];
                }

                if ($arrayLine['cantidad'] == 0) {
                    continue;
                }

                $newLine = $newDoc->getNewLine($arrayLine);
                if (!$newLine->save()) {
                    break;
                }

                $docTrans->model1 = $line->modelClassName();
                $docTrans->iddoc1 = $prototype->primaryColumn();
                $docTrans->idlinea1 = $line->primaryColumn();
                $docTrans->model2 = $newLine->modelClassName();
                $docTrans->iddoc2 = $newDoc->primaryColumn();
                $docTrans->idlinea2 = $newLine->primaryColumn();
                if (!$docTrans->save()) {
                    break;
                }

                $newLine->updateStock($newDoc->codalmacen);
            }

            // confirm data
            $this->dataBase->commit();
        } catch (\Exception $e) {
            $this->miniLog->alert($e->getMessage());
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
                return false;
            }
        }

        return true;
    }
}
