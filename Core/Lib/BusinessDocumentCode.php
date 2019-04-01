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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Base\BusinessDocument;

/**
 * Description of BusinessDocumentCode
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Juan José Prieto Dzul    <juanjoseprieto88@gmail.com>
 */
class BusinessDocumentCode
{

    /**
     * Generates a new identifier for humans from a document.
     *
     * @param BusinessDocument $document
     */
    public static function getNewCode(&$document)
    {
        $document->numero = static::getNewNumero($document->tableName(), $document->codejercicio, $document->codserie);
        $document->codigo = $document->codejercicio . $document->codserie . $document->numero;
    }

    /**
     * 
     * @param string $tableName
     * @param string $codejercicio
     * @param string $codserie
     *
     * @return string
     */
    private static function getNewNumero($tableName, $codejercicio, $codserie)
    {
        $dataBase = new DataBase();
        $sql = "SELECT MAX(" . $dataBase->sql2Int('numero') . ") as num FROM " . $tableName
            . " WHERE codejercicio = " . $dataBase->var2str($codejercicio)
            . " AND codserie = " . $dataBase->var2str($codserie) . ";";

        $data = $dataBase->select($sql);
        if (!empty($data)) {
            return (string) (1 + (int) $data[0]['num']);
        }

        return '1';
    }
}
