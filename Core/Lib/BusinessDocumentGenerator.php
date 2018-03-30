<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Model\Base\BusinessDocument;

/**
 * Description of BusinessDocumentGenerator
 *
 * @author Carlos García Gómez
 */
class BusinessDocumentGenerator
{

    public function generate(BusinessDocument $prototype, string $newClass)
    {
        $exclude = ['idestado', 'fecha', 'hora'];
        $newDocClass = '\\FacturaScripts\\Dinamic\\Model\\' . $newClass;
        $newDoc = new $newDocClass();
        foreach ($prototype->getModelFields() as $field => $value) {
            if (in_array($field, $exclude)) {
                continue;
            }

            $newDoc->{$field} = $prototype->{$field};
        }

        if ($newDoc->save() && $this->cloneLines($prototype, $newDoc)) {
            return true;
        }

        return false;
    }

    private function cloneLines(BusinessDocument $prototype, $newDoc)
    {
        foreach ($prototype->getLines() as $line) {
            $arrayLine = [];
            foreach ($line->getModelFields() as $field => $value) {
                $arrayLine[$field] = $line->{$field};
            }

            $newLine = $newDoc->getNewLine($arrayLine);
            if (!$newLine->save()) {
                return false;
            }
        }

        return true;
    }
}
