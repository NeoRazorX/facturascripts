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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\SecuenciaDocumento;

/**
 * Description of BusinessDocumentCode
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Juan José Prieto Dzul    <juanjoseprieto88@gmail.com>
 */
class BusinessDocumentCode
{

    const GAP_LIMIT = 1000;

    /**
     * Generates a new identifier for humans from a document.
     *
     * @param BusinessDocument $document
     * @param bool $newNumber
     */
    public static function getNewCode(&$document, bool $newNumber = true)
    {
        $sequence = static::getSequence($document);
        if ($newNumber) {
            $document->numero = static::getNewNumber($sequence, $document);
        }

        $document->codigo = CodePatterns::trans(
            $sequence->patron, $document, ['long' => $sequence->longnumero]
        );
    }

    /**
     * @param SecuenciaDocumento $sequence
     * @param BusinessDocument $document
     *
     * @return string
     */
    protected static function getNewNumber(&$sequence, &$document): string
    {
        $previous = static::getPrevious($sequence, $document);

        // find maximum number for this sequence data
        foreach ($previous as $lastDoc) {
            $lastNumber = (int)$lastDoc->numero;
            if ($lastNumber >= $sequence->numero || $sequence->usarhuecos) {
                $sequence->numero = $lastNumber + 1;
            }
            break;
        }

        // use gaps?
        if ($sequence->usarhuecos) {
            // we look for holes back
            $expectedNumber = $sequence->numero - 1;
            $preDate = $document->fecha;
            $preHour = $document->hora;
            foreach ($previous as $preDoc) {
                if ($expectedNumber != $preDoc->numero && $expectedNumber >= $sequence->inicio) {
                    // hole found
                    $document->fecha = $preDate;
                    $document->hora = $preHour;
                    return (string)$expectedNumber;
                }

                $expectedNumber--;
                $preDate = $preDoc->fecha;
                $preHour = $preDoc->hora;
            }

            if (empty($previous)) {
                // no previous document, then use initial number
                $sequence->numero = $sequence->inicio;
            } elseif ($expectedNumber >= $sequence->inicio && $expectedNumber >= $sequence->numero - self::GAP_LIMIT) {
                // the gap is in the first positions of the range
                $document->fecha = $preDate;
                $document->hora = $preHour;
                return (string)$expectedNumber;
            }
        }

        $newNumber = $sequence->numero;

        // update sequence
        $sequence->numero++;
        $sequence->save();

        return (string)$newNumber;
    }

    /**
     * @param SecuenciaDocumento $sequence
     * @param BusinessDocument $document
     *
     * @return BusinessDocument[]
     */
    protected static function getPrevious(&$sequence, &$document): array
    {
        $order = strtolower(FS_DB_TYPE) == 'postgresql' ? ['CAST(numero as integer)' => 'DESC'] : ['CAST(numero as unsigned)' => 'DESC'];
        $where = [
            new DataBaseWhere('codserie', $sequence->codserie),
            new DataBaseWhere('idempresa', $sequence->idempresa)
        ];
        if ($sequence->codejercicio) {
            $where[] = new DataBaseWhere('codejercicio', $sequence->codejercicio);
        }
        return $document->all($where, $order, 0, self::GAP_LIMIT);
    }

    /**
     * Finds sequence for this document.
     *
     * @param BusinessDocument $document
     *
     * @return SecuenciaDocumento
     */
    protected static function getSequence(&$document)
    {
        $selectedSequence = new SecuenciaDocumento();
        $patron = substr(strtoupper($document->modelClassName()), 0, 3) . '{EJE}{SERIE}{NUM}';
        $long = $selectedSequence->longnumero;

        // find sequence for this document and serie
        $sequence = new SecuenciaDocumento();
        $where = [
            new DataBaseWhere('codserie', $document->codserie),
            new DataBaseWhere('idempresa', $document->idempresa),
            new DataBaseWhere('tipodoc', $document->modelClassName())
        ];
        foreach ($sequence->all($where) as $seq) {
            if (empty($seq->codejercicio)) {
                // sequence for all exercises
                $selectedSequence = $seq;
            } elseif ($seq->codejercicio == $document->codejercicio) {
                // sequence for this exercise
                return $seq;
            }

            // use old pattern for the new sequence
            $patron = $seq->patron;
            $long = $seq->longnumero;
        }

        // sequence not found? Then create
        if (false === $selectedSequence->exists()) {
            $selectedSequence->codejercicio = $document->codejercicio;
            $selectedSequence->codserie = $document->codserie;
            $selectedSequence->idempresa = $document->idempresa;
            $selectedSequence->patron = $patron;
            $selectedSequence->longnumero = $long;
            $selectedSequence->tipodoc = $document->modelClassName();
            $selectedSequence->usarhuecos = ('FacturaCliente' === $document->modelClassName());
            $selectedSequence->save();
        }

        return $selectedSequence;
    }
}
