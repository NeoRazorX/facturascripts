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
use FacturaScripts\Dinamic\Model\LineaFacturaProveedor as DinLineaFactura;
use FacturaScripts\Dinamic\Model\ReciboProveedor as DinReciboProveedor;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;

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

    public static function tableName(): string
    {
        return 'facturasprov';
    }
    
    /** * Re-number the accounting entries of the open exercises. * 
     * 
     * @param string $codejercicio 
     * 
     * @return bool 
     */ 
    public function renumberInvoices($codejercicio = '') 
    { 
        $exerciseModel = new DinEjercicio(); 
        $where = empty($codejercicio) ? [] : [new DataBaseWhere('codejercicio', $codejercicio)]; 
        foreach ($exerciseModel->all($where) as $exe) 
        { 
            if (false === $exe->isOpened()) 
            { 
                continue; 
            }

            $offset = 0; 
            $number = 1; 

            $sql = 'SELECT idfactura,numero,fecha FROM ' . static::tableName() 
                 . ' WHERE codejercicio = ' . self::$dataBase->var2str($exe->codejercicio) 
                 . ' ORDER BY codejercicio ASC, fecha ASC, idfactura ASC'; 

            $rows = self::$dataBase->selectLimit($sql, self::RENUMBER_LIMIT, $offset); 
            while (!empty($rows)) 
            { 
                if (false === $this->renumberInvoiceEntries($rows, $number)) 
                { 
                    $this->toolBox()->i18nLog()->warning('renumber-invoices-error', ['%exerciseCode%' => $exe->codejercicio]); 
                    return false; 
                } 

                $offset += self::RENUMBER_LIMIT; 
                $rows = self::$dataBase->selectLimit($sql, self::RENUMBER_LIMIT, $offset); 
            } 
        } 
        return true; 
    }

    /**
    * Update accounting entry numbers.
    *
    * @param array $entries
    * @param int $number
    *
    * @return bool
    */
    protected function renumberInvoiceEntries(&$entries, &$number)
    {
        $sql = '';
        foreach ($entries as $row) {
        if (self::$dataBase->var2str($row['numero']) !== self::$dataBase->var2str($number)) 
        {
            $sql .= 'UPDATE ' . static::tableName() . ' SET numero = ' . self::$dataBase->var2str($number)
                 . ' WHERE idfactura = ' . self::$dataBase->var2str($row['idfactura']) . ';';
        }
        ++$number;
        }
        return empty($sql) || self::$dataBase->exec($sql);
    }
}
