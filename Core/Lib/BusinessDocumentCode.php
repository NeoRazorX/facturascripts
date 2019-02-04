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
     *
     * @return array
     */
    public static function getNewCode(&$document)
    {
        $dataBase = new DataBase();
        $numero = '1';
        
        $sql = "SELECT MAX(" . $dataBase->sql2Int('numero') . ") as num FROM " . $document::tableName()
            . " WHERE codejercicio = " . $dataBase->var2str($document->codejercicio)
            . " AND codserie = " . $dataBase->var2str($document->codserie)
            . " AND idempresa = " . $dataBase->var2str($document->idempresa) . ";";

        $data = $dataBase->select($sql);
        if (!empty($data)) {
            $numero = (string) (1 + (int) $data[0]['num']);
        }

        $codigo = $document->codejercicio . $document->codserie . $numero;

        return ['codigo' => $codigo, 'numero' => $numero];
    }
}
