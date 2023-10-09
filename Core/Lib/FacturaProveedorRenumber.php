<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\BusinessDocumentCode as DinBusinessDocumentCode;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\SecuenciaDocumento;

class FacturaProveedorRenumber
{
    const RENUMBER_LIMIT = 1000;

    /** @var DataBase */
    protected static $db;

    public static function run(string $codejercicio): bool
    {
        $exercise = new Ejercicio();
        if (false === $exercise->loadFromCode($codejercicio)) {
            Tools::log()->error('exercise-not-found', ['%code%' => $codejercicio]);
            return false;
        }

        if (false === $exercise->isOpened()) {
            Tools::log()->error('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        self::$db = new DataBase();
        self::$db->beginTransaction();

        // cada serie tiene numeración independiente
        foreach (Series::all() as $serie) {
            // ordenamos facturas por fecha y hora
            $sql = 'SELECT idfactura,codejercicio,codigo,numero,fecha,hora FROM ' . FacturaProveedor::tableName()
                . ' WHERE codejercicio = ' . self::$db->var2str($exercise->codejercicio)
                . ' AND codserie = ' . self::$db->var2str($serie->codserie)
                . ' ORDER BY fecha ASC, hora ASC, idfactura ASC';
            $offset = 0;
            $rows = self::$db->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
            if (empty($rows)) {
                continue;
            }

            // obtenemos una factura de muestra para obtener la secuencia
            $sample = new FacturaProveedor();
            if (false === $sample->loadFromCode($rows[0]['idfactura'])) {
                Tools::log()->error('sample-invoice-not-found-' . $rows[0]['idfactura']);
                self::$db->rollback();
                return false;
            }

            // obtenemos la secuencia para saber en qué número comenzar
            $sequence = DinBusinessDocumentCode::getSequence($sample);
            $number = $sequence->inicio;

            while (!empty($rows)) {
                if (false === static::renumberInvoices($rows, $number, $serie, $sequence)) {
                    Tools::log()->warning('renumber-invoices-error', ['%code%' => $codejercicio]);
                    self::$db->rollback();
                    return false;
                }

                $offset += self::RENUMBER_LIMIT;
                $rows = self::$db->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
            }
        }

        self::$db->commit();
        return true;
    }

    protected static function renumberInvoices(array &$entries, int &$number, Serie $serie, SecuenciaDocumento $sequence): bool
    {
        $sql = '';
        foreach ($entries as $row) {
            if (self::$db->var2str($row['numero']) !== self::$db->var2str($number)) {
                $document = new FacturaProveedor();
                if (false === $document->loadFromCode($row['idfactura'])) {
                    Tools::log()->error('invoice-not-found-' . $row['idfactura']);
                    break;
                }

                $document->numero = $number;
                $codigo = DinBusinessDocumentCode::getNewCode($sequence, $document);
                $altNumber = $document->newCode('numero') + intval($document->numero);

                // modificamos la factura que pueda tener ya el número y código que vamos a asignar
                $sql .= 'UPDATE ' . FacturaProveedor::tableName()
                    . ' SET numero = ' . self::$db->var2str($altNumber)
                    . ', codigo = ' . self::$db->var2str('???-' . $altNumber)
                    . ' WHERE codejercicio = ' . self::$db->var2str($row['codejercicio'])
                    . ' AND (codigo = ' . self::$db->var2str($codigo)
                    . ' OR (codserie = ' . self::$db->var2str($serie->codserie)
                    . ' AND numero = ' . self::$db->var2str($number) . ')); ';

                // asignamos el nuevo número y código a la factura
                $sql .= 'UPDATE ' . FacturaProveedor::tableName()
                    . ' SET numero = ' . self::$db->var2str($number)
                    . ', codigo = ' . self::$db->var2str($codigo)
                    . ' WHERE idfactura = ' . self::$db->var2str($row['idfactura']) . '; ';

                // modificamos los recibos de la factura
                $sql .= 'UPDATE recibospagosprov'
                    . ' SET codigofactura = ' . self::$db->var2str($codigo)
                    . ' WHERE idfactura = ' . self::$db->var2str($row['idfactura']) . '; ';

                if ($document->idasiento) {
                    // modificamos el asiento de la factura
                    $sql .= 'UPDATE asientos'
                        . ' SET documento = ' . self::$db->var2str($codigo)
                        . ', concepto = REPLACE(concepto, ' . self::$db->var2str($row['codigo'])
                        . ', ' . self::$db->var2str($codigo) . ')'
                        . ' WHERE idasiento = ' . self::$db->var2str($document->idasiento) . '; ';

                    // modificamos las partidas del asiento de la factura
                    $sql .= 'UPDATE partidas'
                        . ' SET concepto = REPLACE(concepto, ' . self::$db->var2str($row['codigo'])
                        . ', ' . self::$db->var2str($codigo) . ')'
                        . ' WHERE idasiento = ' . self::$db->var2str($document->idasiento) . '; ';
                }
            }
            ++$number;
        }
        return empty($sql) || self::$db->exec($sql);
    }
}
