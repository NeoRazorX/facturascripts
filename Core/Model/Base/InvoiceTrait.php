<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\Asiento;

/**
 * Trait con la funcionalidad común de las facturas.
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

    abstract public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 0): array;

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

        // eliminamos los recibos
        foreach ($this->getReceipts() as $receipt) {
            $receipt->disableInvoiceUpdate(true);
            if (false === $receipt->delete()) {
                Tools::log()->warning('cant-remove-receipt');
                return false;
            }
        }

        // eliminamos la contabilidad
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
            $where = [Where::eq('idfacturarect', $this->idfactura)];
            $this->refunds = $this->all($where, ['idfactura' => 'DESC'], 0, 0);
        }

        return $this->refunds;
    }

    /**
     * Esta función se ejecuta al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará después de la creación de la tabla. Útil para insertar valores
     * por defecto.
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
     * Devuelve todos los documentos padre de este.
     *
     * @return TransformerDocument[]
     */
    public function parentDocuments(): array
    {
        $parents = parent::parentDocuments();
        $where = [Where::eq('idfactura', $this->idfacturarect)];
        foreach ($this->all($where, ['idfactura' => 'DESC'], 0, 0) as $invoice) {
            // ¿está esta factura en los padres?
            foreach ($parents as $parent) {
                if ($parent->primaryColumnValue() == $invoice->primaryColumnValue()) {
                    continue 2;
                }
            }

            $parents[] = $invoice;
        }

        return $parents;
    }

    public static function primaryColumn(): string
    {
        return 'idfactura';
    }

    protected function onChange(string $field): bool
    {
        if (false === parent::onChange($field)) {
            return false;
        }

        switch ($field) {
            case 'codcliente':
            case 'codproveedor':
                // evitamos eliminar recibos pagados
                foreach ($this->getReceipts() as $receipt) {
                    if ($receipt->pagado) {
                        Tools::log()->warning('paid-receipts-prevent-action');
                        return false;
                    }
                }
            // no break
            case 'codpago':
                // eliminamos los recibos no pagados
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
        // eliminamos el asiento contable
        $asiento = $this->getAccountingEntry();
        $asiento->editable = true;
        if ($asiento->exists() && false === $asiento->delete()) {
            Tools::log()->warning('cant-remove-account-entry');
            return false;
        }

        // creamos un nuevo asiento contable
        $this->idasiento = null;
        $tool = new InvoiceToAccounting();
        $tool->generate($this);

        // comprobamos los recibos
        $generator = new ReceiptGenerator();
        $generator->generate($this);
        $generator->update($this);

        return true;
    }
}
