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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\Asiento;

/**
 * Description of InvoiceTrait
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait InvoiceTrait
{
    use AccEntryRelationTrait;

    /** @var string */
    public $codigorect;

    /** @var bool */
    public $editable;

    /** @var string */
    public $fecha;

    /** @var string */
    public $fechadevengo;

    /** @var int */
    public $idfactura;

    /** @var int */
    public $idfacturarect;

    /** @var bool */
    public $pagada;

    /** @var array */
    private $refunds;

    /** @return bool */
    public $vencida;

    abstract public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50): array;

    abstract public function getReceipts(): array;

    abstract public function testDate(): bool;

    public function delete(): bool
    {
        if (false === $this->editable) {
            Tools::log()->warning('non-editable-document');
            return false;
        }

        // si tiene rectificativas, no se puede eliminar
        if (!empty($this->getRefunds())) {
            Tools::log()->warning('cant-remove-invoice-refund');
            return false;
        }

        // remove receipts
        foreach ($this->getReceipts() as $receipt) {
            $receipt->disableInvoiceUpdate(true);
            if (false === $receipt->delete()) {
                Tools::log()->warning('cant-remove-receipt');
                return false;
            }
        }

        // remove accounting
        $acEntry = $this->getAccountingEntry();
        $acEntry->editable = true;
        if ($acEntry->exists() && false === $acEntry->delete()) {
            Tools::log()->warning('cant-remove-accounting-entry');
            return false;
        }

        return parent::delete();
    }

    /**
     * @return static[]
     */
    public function getRefunds(): array
    {
        if (empty($this->idfactura)) {
            return [];
        }

        if (!isset($this->refunds)) {
            $where = [new DataBaseWhere('idfacturarect', $this->idfactura)];
            $this->refunds = $this->all($where, ['idfactura' => 'DESC'], 0, 0);
        }

        return $this->refunds;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install(): string
    {
        $sql = parent::install();
        new Asiento();

        return $sql;
    }

    public function paid(): bool
    {
        return $this->pagada;
    }

    /**
     * Returns all parent document of this one.
     *
     * @return TransformerDocument[]
     */
    public function parentDocuments(): array
    {
        $parents = parent::parentDocuments();
        $where = [new DataBaseWhere('idfactura', $this->idfacturarect)];
        foreach ($this->all($where, ['idfactura' => 'DESC'], 0, 0) as $invoice) {
            // is this invoice in parents?
            foreach ($parents as $parent) {
                if ($parent->primaryColumnValue() == $invoice->primaryColumnValue()) {
                    continue 2;
                }
            }

            $parents[] = $invoice;
        }

        return $parents;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idfactura';
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        if (false === parent::onChange($field)) {
            return false;
        }

        switch ($field) {
            case 'codcliente':
            case 'codproveedor':
                // prevent from removing paid receipts
                foreach ($this->getReceipts() as $receipt) {
                    if ($receipt->pagado) {
                        Tools::log()->warning('paid-receipts-prevent-action');
                        return false;
                    }
                }
            // no break
            case 'codpago':
                // remove unpaid receipts
                foreach ($this->getReceipts() as $receipt) {
                    if (false === $receipt->pagado && false === $receipt->delete()) {
                        Tools::log()->warning('cant-remove-receipt');
                        return false;
                    }
                }
            // no break
            case 'fecha':
                if (false === $this->testDate()) {
                    return false;
                }
            // no break
            case 'fechadevengo':
            case 'total':
                return $this->onChangeTotal();

            case 'codserie':
                if (false === $this->testDate()) {
                    return false;
                }
                break;
        }

        return true;
    }

    protected function onChangeTotal(): bool
    {
        // remove accounting entry
        $asiento = $this->getAccountingEntry();
        $asiento->editable = true;
        if ($asiento->exists() && false === $asiento->delete()) {
            Tools::log()->warning('cant-remove-account-entry');
            return false;
        }

        // create a new accounting entry
        $this->idasiento = null;
        $tool = new InvoiceToAccounting();
        $tool->generate($this);

        // check receipts
        $generator = new ReceiptGenerator();
        $generator->generate($this);
        $generator->update($this);

        return true;
    }

    protected function setPreviousData(array $fields = [])
    {
        $more = ['fechadevengo'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
