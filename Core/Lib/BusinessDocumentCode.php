<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * @param bool             $newNumber
     */
    public static function getNewCode(&$document, $newNumber = true)
    {
        $sequence = static::getSequence($document);
        if ($newNumber) {
            $document->numero = static::getNewNumber($sequence, $document);
        }

        $document->codigo = \strtr($sequence->patron, [
            '{EJE}' => $document->codejercicio,
            '{EJE2}' => \substr($document->codejercicio, -2),
            '{SERIE}' => $document->codserie,
            '{0SERIE}' => \str_pad($document->codserie, 2, '0', \STR_PAD_LEFT),
            '{NUM}' => $document->numero,
            '{0NUM}' => \str_pad($document->numero, $sequence->longnumero, '0', \STR_PAD_LEFT)
        ]);
    }

    /**
     * 
     * @param SecuenciaDocumento $sequence
     * @param BusinessDocument   $document
     *
     * @return string
     */
    protected static function getNewNumber(&$sequence, &$document)
    {
        // get previous
        $order = \strtolower(\FS_DB_TYPE) == 'postgresql' ? ['CAST(numero as integer)' => 'DESC'] : ['CAST(numero as unsigned)' => 'DESC'];
        $where = [
            new DataBaseWhere('codserie', $sequence->codserie),
            new DataBaseWhere('idempresa', $sequence->idempresa)
        ];
        if (!empty($sequence->codejercicio)) {
            $where[] = new DataBaseWhere('codejercicio', $sequence->codejercicio);
        }
        $previous = $document->all($where, $order, 0, self::GAP_LIMIT);

        /// find maximum number for this sequence data
        foreach ($previous as $lastDoc) {
            $lastNumber = (int) $lastDoc->numero;
            if ($lastNumber >= $sequence->numero || $sequence->usarhuecos) {
                $sequence->numero = $lastNumber + 1;
            }
            break;
        }

        /// use gaps?
        if ($sequence->usarhuecos) {
            /// we look for holes back
            $expectedNumber = (int) $sequence->numero - 1;
            foreach ($previous as $lastDoc) {
                if ($expectedNumber != $lastDoc->numero) {
                    $document->fecha = $lastDoc->fecha;
                    $document->hora = $lastDoc->hora;
                    break;
                }

                $expectedNumber--;
            }

            if (empty($previous)) {
                /// no previous document, then use initial number
                $sequence->numero = $sequence->inicio;
            } elseif ($expectedNumber >= $sequence->inicio && $expectedNumber > (int) $sequence->numero - self::GAP_LIMIT - 1) {
                return (string) $expectedNumber;
            }
        }

        $newNumber = $sequence->numero;

        /// update sequence
        $sequence->numero++;
        $sequence->save();

        return (string) $newNumber;
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

        /// find sequence for this document and serie
        $sequence = new SecuenciaDocumento();
        $where = [
            new DataBaseWhere('codserie', $document->codserie),
            new DataBaseWhere('idempresa', $document->idempresa),
            new DataBaseWhere('tipodoc', $document->modelClassName()),
        ];
        foreach ($sequence->all($where) as $seq) {
            if (empty($seq->codejercicio)) {
                /// sequence for all exercises
                $selectedSequence = $seq;
            } elseif ($seq->codejercicio == $document->codejercicio) {
                /// sequence for this exercise
                return $seq;
            }
        }

        /// sequence not found? Then create
        if (!$selectedSequence->exists()) {
            $selectedSequence->codejercicio = $document->codejercicio;
            $selectedSequence->codserie = $document->codserie;
            $selectedSequence->idempresa = $document->idempresa;
            $selectedSequence->tipodoc = $document->modelClassName();
            $selectedSequence->usarhuecos = ('FacturaCliente' === $document->modelClassName());
            $selectedSequence->save();
        }

        return $selectedSequence;
    }
}
