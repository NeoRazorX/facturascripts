<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Dinamic\Lib\BusinessDocumentCode;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;
use FacturaScripts\Dinamic\Model\LineaFacturaProveedor as DinLineaFactura;
use FacturaScripts\Dinamic\Model\ReciboProveedor as DinReciboProveedor;
use FacturaScripts\Dinamic\Model\SecuenciaDocumento as DinSecuenciaDocumento;

/**
 * Invoice from a supplier.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FacturaProveedor extends Base\PurchaseDocument
{
    use Base\ModelTrait;
    use Base\InvoiceTrait;

    const RENUMBER_LIMIT = 1000;

    public function clear()
    {
        parent::clear();
        $this->pagada = false;
    }

    /**
     * Returns the lines associated with the invoice.
     *
     * @return DinLineaFactura[]
     */
    public function getLines(): array
    {
        $lineaModel = new DinLineaFactura();
        $where = [new DataBaseWhere('idfactura', $this->idfactura)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];
        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for the document.
     *
     * @param array $data
     * @param array $exclude
     *
     * @return DinLineaFactura
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idfactura', 'servido'])
    {
        $newLine = new DinLineaFactura();
        $newLine->idfactura = $this->idfactura;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = $this->getStatus()->actualizastock;
        $newLine->loadFromData($data, $exclude);

        // allow extensions
        $this->pipe('getNewLine', $newLine, $data, $exclude);

        return $newLine;
    }

    /**
     * Returns all invoice's receipts.
     *
     * @return DinReciboProveedor[]
     */
    public function getReceipts(): array
    {
        $receipt = new DinReciboProveedor();
        $where = [new DataBaseWhere('idfactura', $this->idfactura)];
        return $receipt->all($where, ['numero' => 'ASC', 'idrecibo' => 'ASC'], 0, 0);
    }

    /**
     * Renumber all the invoices on the given exercise.
     *
     * @param string $codejercicio
     *
     * @return bool
     */
    public function renumber(string $codejercicio): bool
    {
        $exercise = new DinEjercicio();
        if (false === $exercise->loadFromCode($codejercicio)) {
            self::toolBox()::i18nLog()->error('exercise-not-found', ['%code%' => $codejercicio]);
            return false;
        } elseif (false === $exercise->isOpened()) {
            self::toolBox()::i18nLog()->error('closed-exercise', ['%exerciseName%' => $exercise->nombre]);
            return false;
        }

        // cada serie tiene numeración independiente
        foreach (Series::all() as $serie) {
            // ordenamos facturas por fecha y hora
            $sql = 'SELECT idfactura,numero,fecha,hora FROM ' . static::tableName()
                . ' WHERE codejercicio = ' . self::$dataBase->var2str($exercise->codejercicio)
                . ' AND codserie = ' . self::$dataBase->var2str($serie->codserie)
                . ' ORDER BY fecha ASC, hora ASC, idfactura ASC';
            $offset = 0;
            $rows = self::$dataBase->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
            if (empty($rows)) {
                continue;
            }

            // obtenemos la secuencia para saber en qué número comenzar
            $sample = $this->get($rows[0]['idfactura']);
            $sequence = BusinessDocumentCode::getSequence($sample);
            $number = $sequence->inicio;

            while (!empty($rows)) {
                if (false === $this->renumberInvoices($rows, $number, $sequence)) {
                    self::toolBox()::i18nLog()->warning('renumber-invoices-error', ['%code%' => $codejercicio]);
                    return false;
                }

                $offset += self::RENUMBER_LIMIT;
                $rows = self::$dataBase->selectLimit($sql, self::RENUMBER_LIMIT, $offset);
            }
        }

        return true;
    }

    public static function tableName(): string
    {
        return 'facturasprov';
    }

    /**
     * Update invoice numbers.
     *
     * @param array $entries
     * @param int $number
     * @param DinSecuenciaDocumento $sequence
     *
     * @return bool
     */
    protected function renumberInvoices(array &$entries, int &$number, DinSecuenciaDocumento $sequence): bool
    {
        $sql = '';
        foreach ($entries as $row) {
            if (self::$dataBase->var2str($row['numero']) !== self::$dataBase->var2str($number)) {
                $document = $this->get($row['idfactura']);
                $document->numero = $number;
                $codigo = BusinessDocumentCode::getNewCode($sequence, $document);

                $sql .= 'UPDATE ' . static::tableName()
                    . ' SET codigo = ' . self::$dataBase->var2str('???-' . $number)
                    . ' WHERE codigo = ' . self::$dataBase->var2str($codigo) . ';'
                    . ' UPDATE ' . static::tableName()
                    . ' SET numero = ' . self::$dataBase->var2str($number) . ', codigo = ' . self::$dataBase->var2str($codigo)
                    . ' WHERE idfactura = ' . self::$dataBase->var2str($row['idfactura']) . ';';
            }
            ++$number;
        }
        return empty($sql) || self::$dataBase->exec($sql);
    }
}
