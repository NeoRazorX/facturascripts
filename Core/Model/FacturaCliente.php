<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente as DinLineaFactura;
use FacturaScripts\Dinamic\Model\ReciboCliente as DinReciboCliente;

/**
 * Customer's invoice.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FacturaCliente extends Base\SalesDocument
{
    use Base\ModelTrait;
    use Base\InvoiceTrait;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        self::$dont_copy_fields[] = 'fechadevengo';
    }

    public function clear()
    {
        parent::clear();
        $this->pagada = false;
        $this->vencida = false;
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
     * @return DinReciboCliente[]
     */
    public function getReceipts(): array
    {
        $receipt = new DinReciboCliente();
        $where = [new DataBaseWhere('idfactura', $this->idfactura)];
        return $receipt->all($where, ['numero' => 'ASC', 'idrecibo' => 'ASC'], 0, 0);
    }

    public static function tableName(): string
    {
        return 'facturascli';
    }

    protected function saveInsert(array $values = []): bool
    {
        return $this->testDate() && parent::saveInsert($values);
    }

    protected function testDate(): bool
    {
        // prevent form using old dates
        $numColumn = strtolower(FS_DB_TYPE) == 'postgresql' ? 'CAST(numero as integer)' : 'CAST(numero as unsigned)';
        $whereOld = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codserie', $this->codserie),
            new DataBaseWhere($numColumn, (int)$this->numero, '<')
        ];
        foreach ($this->all($whereOld, ['fecha' => 'DESC'], 0, 1) as $old) {
            if (strtotime($old->fecha) > strtotime($this->fecha)) {
                Tools::log()->error(
                    'invalid-date-there-are-invoices-before',
                    ['%date%' => $this->fecha, '%other-date%' => $old->fecha, '%other%' => $old->codigo]
                );
                return false;
            }
        }

        // prevent the use of too new dates
        $whereNew = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codserie', $this->codserie),
            new DataBaseWhere($numColumn, (int)$this->numero, '>')
        ];
        foreach ($this->all($whereNew, ['fecha' => 'ASC'], 0, 1) as $old) {
            if (strtotime($old->fecha) < strtotime($this->fecha)) {
                Tools::log()->error(
                    'invalid-date-there-are-invoices-after',
                    ['%date%' => $this->fecha, '%other-date%' => $old->fecha, '%other%' => $old->codigo]
                );
                return false;
            }
        }

        return true;
    }
}
