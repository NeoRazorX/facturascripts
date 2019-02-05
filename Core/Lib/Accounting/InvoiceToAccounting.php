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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Class for the generation of accounting entries of a sale/purchase document
 * and the settlement of your receipts.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class InvoiceToAccounting extends AccountingGenerator
{

    /**
     * Document Model with data to process
     *
     * @var FacturaCliente|FacturaProveedor
     */
    protected $document;

    /**
     * Accounting exercise model
     *
     * @var Ejercicio
     */
    protected $exercise;

    /**
     * Subaccounting plan model
     *
     * @var Subcuenta
     */
    protected $subaccount;

    /**
     * Document Subtotals Lines array
     *
     * @var array
     */
    protected $subtotals;

    /**
     * VAT model
     *
     * @var Impuesto
     */
    protected $vat;

    /**
     * Class constructor and initializate auxiliar model class.
     */
    public function __construct()
    {
        parent::__construct();
        $this->exercise = new Ejercicio();
        $this->subaccount = new Subcuenta();
        $this->vat = new Impuesto();
    }

    /**
     * 
     * @param Cliente $customer
     * @param string  $codejercicio
     *
     * @return Subcuenta
     */
    public function createCustomerAccount(&$customer, $codejercicio)
    {
        $subcuenta = new Subcuenta();
        if (!$this->exercise->loadFromCode($codejercicio) || !$this->exercise->abierto()) {
            return $subcuenta;
        }

        $cuenta = new Cuenta();
        $where = [
            new DataBaseWhere('codejercicio', $codejercicio),
            new DataBaseWhere('codcuenta', $this->getPrefixAccount($codejercicio, 'CLIENT', '4300'))
        ];
        if (!$cuenta->loadFromCode('', $where)) {
            return $subcuenta;
        }

        $subcuenta->codcuenta = $cuenta->codcuenta;
        $subcuenta->codejercicio = $codejercicio;
        $subcuenta->codsubcuenta = $this->fillToLength($this->exercise->longsubcuenta, $customer->primaryColumnValue(), $cuenta->codcuenta);
        $subcuenta->descripcion = $customer->razonsocial;
        $subcuenta->idcuenta = $cuenta->idcuenta;
        $subcuenta->save();

        return $subcuenta;
    }

    /**
     * 
     * @param Proveedor $supplier
     * @param string    $codejercicio
     *
     * @return Subcuenta
     */
    public function createSupplierAccount(&$supplier, $codejercicio)
    {
        $subcuenta = new Subcuenta();
        if (!$this->exercise->loadFromCode($codejercicio) || !$this->exercise->abierto()) {
            return $subcuenta;
        }

        $cuenta = new Cuenta();
        $where = [
            new DataBaseWhere('codejercicio', $codejercicio),
            new DataBaseWhere('codcuenta', $this->getPrefixAccount($codejercicio, 'PROVEE', '4000'))
        ];
        if (!$cuenta->loadFromCode('', $where)) {
            return $subcuenta;
        }

        $subcuenta->codcuenta = $cuenta->codcuenta;
        $subcuenta->codejercicio = $codejercicio;
        $subcuenta->codsubcuenta = $this->fillToLength($this->exercise->longsubcuenta, $supplier->primaryColumnValue(), $cuenta->codcuenta);
        $subcuenta->descripcion = $supplier->razonsocial;
        $subcuenta->idcuenta = $cuenta->idcuenta;
        $subcuenta->save();

        return $subcuenta;
    }

    /**
     * 
     * @param FacturaCliente|FacturaProveedor $model
     */
    public function generate(&$model)
    {
        $this->document = $model;

        if (!empty($model->idasiento)) {
            return;
        } elseif (!$this->exercise->loadFromCode($model->codejercicio) || !$this->exercise->abierto()) {
            $this->miniLog->warning($this->i18n->trans('closed-exercise'));
            return;
        } elseif (!$this->loadSubtotals()) {
            return;
        }

        switch ($model->modelClassName()) {
            case 'FacturaCliente':
                $this->salesAccountingEntry();
                break;

            case 'FacturaProveedor':
                $this->purchaseAccountingEntry();
                break;
        }
    }

    /**
     * 
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addCustomerLine($accountEntry)
    {
        $cliente = new Cliente();
        if (!$cliente->loadFromCode($this->document->codcliente)) {
            $this->miniLog->warning($this->i18n->trans('customer-not-found'));
            return false;
        }

        $subcuenta = $cliente->getAccount($this->exercise->codejercicio, true);
        if (!$subcuenta->exists()) {
            $this->miniLog->warning($this->i18n->trans('customer-account-not-found'));
            return false;
        }

        $line = $accountEntry->getNewLine();
        $line->codsubcuenta = $subcuenta->codsubcuenta;
        $line->debe = $this->document->total;
        $line->idsubcuenta = $subcuenta->idsubcuenta;
        return $line->save();
    }

    /**
     * 
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addGoodsPurchaseLine($accountEntry)
    {
        $cuenta = new Cuenta();
        $where = [
            new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
            new DataBaseWhere('codcuentaesp', 'COMPRA')
        ];
        if (!$cuenta->loadFromCode('', $where)) {
            $this->miniLog->alert($this->i18n->trans('purchases-account-not-found'));
            return false;
        }

        foreach ($cuenta->getSubcuentas() as $subcuenta) {
            $line = $accountEntry->getNewLine();
            $line->codsubcuenta = $subcuenta->codsubcuenta;
            $line->debe = $this->document->total;
            $line->idsubcuenta = $subcuenta->idsubcuenta;
            return $line->save();
        }

        return false;
    }

    /**
     * 
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addGoodsSalesLine($accountEntry)
    {
        $cuenta = new Cuenta();
        $where = [
            new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
            new DataBaseWhere('codcuentaesp', 'VENTAS')
        ];
        if (!$cuenta->loadFromCode('', $where)) {
            $this->miniLog->alert($this->i18n->trans('sales-account-not-found'));
            return false;
        }

        foreach ($cuenta->getSubcuentas() as $subcuenta) {
            $line = $accountEntry->getNewLine();
            $line->codsubcuenta = $subcuenta->codsubcuenta;
            $line->haber = $this->document->total;
            $line->idsubcuenta = $subcuenta->idsubcuenta;
            return $line->save();
        }

        return false;
    }

    /**
     * 
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addPurchaseTaxLines($accountEntry)
    {
        return true;
    }

    /**
     * 
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addSalesTaxLines($accountEntry)
    {
        return true;
    }

    /**
     * 
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addSupplierLine($accountEntry)
    {
        $proveedor = new Proveedor();
        if (!$proveedor->loadFromCode($this->document->codproveedor)) {
            $this->miniLog->warning($this->i18n->trans('supplier-not-found'));
            return false;
        }

        $subcuenta = $proveedor->getAccount($this->exercise->codejercicio, true);
        if (!$subcuenta->exists()) {
            $this->miniLog->warning($this->i18n->trans('supplier-account-not-found'));
            return false;
        }

        $line = $accountEntry->getNewLine();
        $line->codsubcuenta = $subcuenta->codsubcuenta;
        $line->haber = $this->document->total;
        $line->idsubcuenta = $subcuenta->idsubcuenta;
        return $line->save();
    }

    /**
     * 
     * @return bool
     */
    protected function loadSubtotals(): bool
    {
        $tools = new BusinessDocumentTools();
        $this->subtotals = $tools->getSubtotals($this->document->getLines());
        return !empty($this->document->total);
    }

    /**
     * Generate the accounting entry for a purchase document.
     */
    protected function purchaseAccountingEntry()
    {
        $accountEntry = new Asiento();
        $accountEntry->codejercicio = $this->document->codejercicio;
        $accountEntry->concepto = $this->i18n->trans('supplier-invoice') . ' ' . $this->document->codigo;
        $accountEntry->documento = $this->document->codigo;
        $accountEntry->fecha = $this->document->fecha;
        $accountEntry->idempresa = $this->document->idempresa;
        $accountEntry->importe = $this->document->total;
        if (!$accountEntry->save()) {
            return;
        }

        if ($this->addSupplierLine($accountEntry) && $this->addPurchaseTaxLines($accountEntry) && $this->addGoodsPurchaseLine($accountEntry)) {
            $this->document->idasiento = $accountEntry->primaryColumnValue();
            $this->document->save();
            return;
        }

        $accountEntry->delete();
    }

    /**
     * Generate the accounting entry for a sales document.
     */
    protected function salesAccountingEntry()
    {
        $accountEntry = new Asiento();
        $accountEntry->codejercicio = $this->document->codejercicio;
        $accountEntry->concepto = $this->i18n->trans('customer-invoice') . ' ' . $this->document->codigo;
        $accountEntry->documento = $this->document->codigo;
        $accountEntry->fecha = $this->document->fecha;
        $accountEntry->idempresa = $this->document->idempresa;
        $accountEntry->importe = $this->document->total;
        if (!$accountEntry->save()) {
            return;
        }

        if ($this->addCustomerLine($accountEntry) && $this->addSalesTaxLines($accountEntry) && $this->addGoodsSalesLine($accountEntry)) {
            $this->document->idasiento = $accountEntry->primaryColumnValue();
            $this->document->save();
            return;
        }

        $accountEntry->delete();
    }
}
