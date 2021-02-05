<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\TransformerDocument;
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

    /**
     * Code of the invoice that rectifies.
     *
     * @var string
     */
    public $codigorect;

    /**
     * Indicates whether the document can be modified
     *
     * @var bool
     */
    public $editable;

    /**
     * Date of the document.
     *
     * @var string
     */
    public $fecha;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idfactura;

    /**
     * ID of the invoice that you rectify.
     *
     * @var int
     */
    public $idfacturarect;

    /**
     *
     * @var bool
     */
    public $pagada;

    abstract public function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50);

    abstract public function getLines();

    abstract public function getReceipts();

    abstract protected static function toolBox();

    /**
     * Returns all children documents of this one.
     *
     * @return TransformerDocument[]
     */
    public function childrenDocuments()
    {
        $children = parent::childrenDocuments();
        foreach ($this->getRefunds() as $invoice) {
            /// is this invoice in children?
            $found = false;
            foreach ($children as $child) {
                if ($child->idfactura == $invoice->idfactura) {
                    $found = true;
                    break;
                }
            }

            if (false === $found) {
                $children[] = $invoice;
            }
        }

        return $children;
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        if (false === $this->editable) {
            $this->toolBox()->i18nLog()->warning('non-editable-document');
            return false;
        }

        /// remove receipts
        foreach ($this->getReceipts() as $receipt) {
            $receipt->disableInvoiceUpdate(true);
            if (false === $receipt->delete()) {
                $this->toolBox()->i18nLog()->warning('cant-remove-receipt');
                return false;
            }
        }

        /// remove accounting
        $acEntry = $this->getAccountingEntry();
        $acEntry->editable = true;
        if ($acEntry->exists() && false === $acEntry->delete()) {
            $this->toolBox()->i18nLog()->warning('cant-remove-accounting-entry');
            return false;
        }

        return parent::delete();
    }

    /**
     * 
     * @return static[]
     */
    public function getRefunds()
    {
        if (empty($this->idfactura)) {
            return [];
        }

        $where = [new DataBaseWhere('idfacturarect', $this->idfactura)];
        return $this->all($where, ['idfactura' => 'DESC'], 0, 0);
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        $sql = parent::install();
        new Asiento();

        return $sql;
    }

    /**
     * 
     * @return bool
     */
    public function paid()
    {
        return $this->pagada;
    }

    /**
     * Returns all parent document of this one.
     *
     * @return TransformerDocument[]
     */
    public function parentDocuments()
    {
        $parents = parent::parentDocuments();
        $where = [new DataBaseWhere('idfactura', $this->idfacturarect)];
        foreach ($this->all($where, ['idfactura' => 'DESC'], 0, 0) as $invoice) {
            /// is this invoice in parents?
            $found = false;
            foreach ($parents as $parent) {
                if ($parent->primaryColumnValue() == $invoice->primaryColumnValue()) {
                    $found = true;
                    break;
                }
            }

            if (false === $found) {
                $parents[] = $invoice;
            }
        }

        return $parents;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idfactura';
    }

    /**
     * Returns the refunded items amount associated with the invoice.
     *
     * @return float|int
     */
    public function refundedItemAmount($ref)
    {
        $amount = 0;
        foreach ($this->getRefunds() as $invoice) {
            foreach ($invoice->getLines() as $line) {
                if ($line->referencia == $ref) {
                    $amount += \abs($line->cantidad);
                }
            }
        }

        return $amount;
    }

    /**
     * 
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
                /// prevent from removing paid receipts
                foreach ($this->getReceipts() as $receipt) {
                    if ($receipt->pagado) {
                        $this->toolBox()->i18nLog()->warning('paid-receipts-prevent-action');
                        return false;
                    }
                }
            /// no break
            case 'codpago':
                /// remove unpaid receipts
                foreach ($this->getReceipts() as $receipt) {
                    if (false === $receipt->pagado && false === $receipt->delete()) {
                        $this->toolBox()->i18nLog()->warning('cant-remove-receipt');
                        return false;
                    }
                }
            /// no break
            case 'fecha':
            case 'total':
                return $this->onChangeTotal();
        }

        return true;
    }

    /**
     * 
     * @return bool
     */
    protected function onChangeTotal()
    {
        /// remove accounting entry
        $asiento = $this->getAccountingEntry();
        $asiento->editable = true;
        if ($asiento->exists() && false === $asiento->delete()) {
            $this->toolBox()->i18nLog()->warning('cant-remove-account-entry');
            return false;
        }

        /// create a new accounting entry
        $this->idasiento = null;
        $tool = new InvoiceToAccounting();
        $tool->generate($this);

        /// check receipts
        $generator = new ReceiptGenerator();
        $generator->generate($this);
        $generator->update($this);

        return true;
    }
}
