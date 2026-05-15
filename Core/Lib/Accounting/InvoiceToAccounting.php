<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Join\PurchasesDocIrpfAccount;
use FacturaScripts\Dinamic\Model\Join\PurchasesDocLineAccount;
use FacturaScripts\Dinamic\Model\Join\SalesDocLineAccount;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Retencion;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Clase para la generación de asientos contables a partir de un documento
 * de venta/compra y la liquidación de sus recibos.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Raúl Jiménez Jiménez          <raljopa@gmail.com>
 */
class InvoiceToAccounting extends AccountingClass
{
    /**
     * @var Subcuenta
     */
    protected $counterpart;

    /**
     * @var FacturaCliente|FacturaProveedor
     */
    protected $document;

    /**
     * Subtotales del documento calculados con Calculator::getSubtotals.
     * Claves: 'iva' (array por tipo de IVA con neto/iva/recargo/totaliva/totalrecargo/codimpuesto),
     * 'irpf' (porcentaje) y 'totalirpf' (importe).
     *
     * @var array
     */
    protected $subtotals;

    /**
     * Método para lanzar el proceso de contabilización
     *
     * @param FacturaCliente|FacturaProveedor $model
     */
    public function generate($model)
    {
        parent::generate($model);
        if (false === $this->initialChecks()) {
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
     * Añade la línea del cliente al asiento y guarda su subcuenta como contrapartida
     * para las siguientes líneas (IVA, IRPF, mercancía).
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addCustomerLine(Asiento $entry): bool
    {
        $customer = new Cliente();
        if (false === $customer->load($this->document->codcliente)) {
            Tools::log()->warning('customer-not-found', ['%customer%' => $this->document->codcliente]);
            $this->counterpart = null;
            return false;
        }

        $subAccount = $this->getCustomerAccount($customer);
        if (false === $subAccount->exists()) {
            Tools::log()->warning('customer-account-not-found', [
                '%customer%' => $this->document->codcliente,
                '%exercise%' => $this->exercise->codejercicio
            ]);
            $this->counterpart = null;
            return false;
        }

        $this->counterpart = $subAccount;
        return $this->addBasicLine($entry, $subAccount, true);
    }

    /**
     * Añade la línea de compra de mercancías al asiento, una por cada subcuenta de
     * producto/familia. En facturas rectificativas usa la cuenta especial DEVCOM si
     * está configurada; en caso contrario cae a COMPRA.
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addGoodsPurchaseLine(Asiento $entry): bool
    {
        $rectifAccount = $this->getSpecialSubAccount('DEVCOM');
        $useRectif = $this->document->idfacturarect && $rectifAccount->exists();
        $purchaseAccount = $useRectif ? $rectifAccount : $this->getSpecialSubAccount('COMPRA');
        if (false === $purchaseAccount->exists()) {
            Tools::log()->warning('special-account-subaccount-not-found', [
                '%account%' => $useRectif ? 'DEVCOM' : 'COMPRA',
                '%exercise%' => $this->exercise->codejercicio
            ]);
            return false;
        }

        $tool = new PurchasesDocLineAccount();
        $totals = $tool->getTotalsForDocument($this->document, $purchaseAccount->codsubcuenta ?? '');
        return $this->addLinesFromTotals(
            $entry,
            $totals,
            true,
            $this->counterpart,
            'purchases-subaccount-not-found',
            'good-purchases-line-error'
        );
    }

    /**
     * Añade la línea de venta de mercancías al asiento, una por cada subcuenta de
     * producto/familia. En facturas rectificativas usa la cuenta especial DEVVEN si
     * está configurada; en caso contrario cae a VENTAS.
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addGoodsSalesLine(Asiento $entry): bool
    {
        $rectifAccount = $this->getSpecialSubAccount('DEVVEN');
        $useRectif = $this->document->idfacturarect && $rectifAccount->exists();
        $salesAccount = $useRectif ? $rectifAccount : $this->getSpecialSubAccount('VENTAS');
        if (false === $salesAccount->exists()) {
            Tools::log()->warning('special-account-subaccount-not-found', [
                '%account%' => $useRectif ? 'DEVVEN' : 'VENTAS',
                '%exercise%' => $this->exercise->codejercicio
            ]);
            return false;
        }

        $tool = new SalesDocLineAccount();
        $totals = $tool->getTotalsForDocument($this->document, $salesAccount->codsubcuenta ?? '');
        return $this->addLinesFromTotals(
            $entry,
            $totals,
            false,
            $this->counterpart,
            'sales-subaccount-not-found',
            'good-sales-line-error'
        );
    }

    /**
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addPurchaseIrpfLines(Asiento $entry): bool
    {
        if (empty($this->document->totalirpf) || count($this->subtotals) == 0) {
            return true;
        }

        $retention = new Retencion();
        if (false === $retention->loadFromPercentage($this->subtotals['irpf'])) {
            Tools::log()->warning('irpf-record-not-found', ['%value%' => $this->subtotals['irpf']]);
            return false;
        }

        $account = $this->getIRPFPurchaseAccount($retention);
        if (false === $account->exists()) {
            Tools::log()->warning('irpfpr-subaccount-not-found', [
                '%subaccount%' => $retention->codsubcuentaacr,
                '%exercise%' => $this->exercise->codejercicio
            ]);
            return false;
        }

        $tool = new PurchasesDocIrpfAccount();
        $totals = $tool->getTotalsForDocument($this->document, $account->codsubcuenta ?? '', $this->subtotals['irpf']);
        return $this->addLinesFromTotals(
            $entry,
            $totals,
            false,
            $this->counterpart,
            'irpf-subaccount-not-found',
            'irpf-purchase-line-error'
        );
    }

    /**
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addPurchaseSuppliedLines(Asiento $entry): bool
    {
        if (empty($this->document->totalsuplidos)) {
            return true;
        }

        $subAccount = $this->getSpecialSubAccount('SUPLI');
        if (false === $subAccount->exists()) {
            Tools::log()->warning('supplied-subaccount-not-found', ['%exercise%' => $this->exercise->codejercicio]);
            return false;
        }

        return $this->addBasicLine($entry, $subAccount, true, $this->document->totalsuplidos);
    }

    /**
     * Añade la línea de impuestos de compras al asiento contable
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addPurchaseTaxLines(Asiento $entry): bool
    {
        $isIntra = in_array($this->document->operacion, [
            InvoiceOperation::INTRA_COMMUNITY,
            InvoiceOperation::INTRA_COMMUNITY_SERVICES,
            InvoiceOperation::REVERSE_CHARGE,
        ]);

        foreach ($this->subtotals['iva'] as $value) {
            $tax = Impuestos::get($value['codimpuesto']);

            // si es intracomunitaria o ISP, añadimos autorepercusión con cuentas intra
            if ($isIntra) {
                $subAccountSup = $tax->getInputIntraTaxAccount($this->exercise->codejercicio);
                if (false === $subAccountSup->exists()) {
                    Tools::log()->warning('ivasop-account-not-found', [
                        '%tax%' => $tax->codimpuesto,
                        '%exercise%' => $this->exercise->codejercicio
                    ]);
                    return false;
                }
                $subAccountImp = $tax->getOutputIntraTaxAccount($this->exercise->codejercicio);
                if (false === $subAccountImp->exists()) {
                    Tools::log()->warning('ivarep-account-not-found', [
                        '%tax%' => $tax->codimpuesto,
                        '%exercise%' => $this->exercise->codejercicio
                    ]);
                    return false;
                }

                // recalculamos el IVA: en autorepercusión la base no lleva IVA repercutido en el documento,
                // así que aquí lo derivamos del neto y forzamos el recargo a 0
                $value['totaliva'] = round($value['neto'] * $value['iva'] / 100, 2);
                $value['totalrecargo'] = 0.0;
                $done = $this->addTaxLine($entry, $subAccountSup, $this->counterpart, true, $value) &&
                    $this->addTaxLine($entry, $subAccountImp, $this->counterpart, false, $value);
                if (false === $done) {
                    return false;
                }
                continue;
            }

            $subAccountSup = $tax->getInputTaxAccount($this->exercise->codejercicio);
            if (false === $subAccountSup->exists()) {
                Tools::log()->warning('ivasop-account-not-found', [
                    '%tax%' => $tax->codimpuesto,
                    '%exercise%' => $this->exercise->codejercicio
                ]);
                return false;
            }
            $subAccountSupSurcharge = $tax->getInputSurchargeAccount($this->exercise->codejercicio);
            if (false === $subAccountSupSurcharge->exists()) {
                Tools::log()->warning('ivasopre-account-not-found', [
                    '%tax%' => $tax->codimpuesto,
                    '%exercise%' => $this->exercise->codejercicio
                ]);
                return false;
            }

            // añadimos la línea de IVA soportado
            $done = $this->addTaxLine($entry, $subAccountSup, $this->counterpart, true, $value) &&
                $this->addSurchargeLine($entry, $subAccountSupSurcharge, $this->counterpart, true, $value);
            if (false === $done) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addSalesIrpfLines(Asiento $entry): bool
    {
        if (empty($this->document->totalirpf) || count($this->subtotals) == 0) {
            return true;
        }

        $retention = new Retencion();
        if (false === $retention->loadFromPercentage($this->subtotals['irpf'])) {
            Tools::log()->warning('irpf-record-not-found', ['%value%' => $this->subtotals['irpf']]);
            return false;
        }

        $account = $this->getIRPFSalesAccount($retention);
        if (false === $account->exists()) {
            Tools::log()->warning('irpf-subaccount-not-found', [
                '%subaccount%' => $retention->codsubcuentaret,
                '%exercise%' => $this->exercise->codejercicio
            ]);
            return false;
        }

        $newLine = $this->getBasicLine($entry, $account, true, $this->subtotals['totalirpf']);
        $newLine->setCounterpart($this->counterpart);
        return $newLine->save();
    }

    /**
     * Añade la línea de suplidos de ventas al asiento contable
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addSalesSuppliedLines(Asiento $entry): bool
    {
        if (empty($this->document->totalsuplidos)) {
            return true;
        }

        $subAccount = $this->getSpecialSubAccount('SUPLI');
        if (false === $subAccount->exists()) {
            Tools::log()->warning('supplied-subaccount-not-found', ['%exercise%' => $this->exercise->codejercicio]);
            return false;
        }

        return $this->addBasicLine($entry, $subAccount, false, $this->document->totalsuplidos);
    }

    /**
     * Añade la línea de impuestos de ventas al asiento contable
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addSalesTaxLines(Asiento $entry): bool
    {
        $isIntra = in_array($this->document->operacion, [
            InvoiceOperation::INTRA_COMMUNITY,
            InvoiceOperation::INTRA_COMMUNITY_SERVICES,
        ]);

        foreach ($this->subtotals['iva'] as $value) {
            $tax = Impuestos::get($value['codimpuesto']);

            // ventas intracomunitarias: autorepercusión con cuentas intra
            if ($isIntra) {
                $subAccOut = $tax->getOutputIntraTaxAccount($this->exercise->codejercicio);
                if (false === $subAccOut->exists()) {
                    Tools::log()->warning('ivarep-subaccount-not-found', [
                        '%tax%' => $tax->codimpuesto,
                        '%exercise%' => $this->exercise->codejercicio
                    ]);
                    return false;
                }
                $subAccIn = $tax->getInputIntraTaxAccount($this->exercise->codejercicio);
                if (false === $subAccIn->exists()) {
                    Tools::log()->warning('ivasop-subaccount-not-found', [
                        '%tax%' => $tax->codimpuesto,
                        '%exercise%' => $this->exercise->codejercicio
                    ]);
                    return false;
                }

                $value['totaliva'] = round($value['neto'] * $value['iva'] / 100, 2);
                $value['totalrecargo'] = 0.0;
                $done = $this->addTaxLine($entry, $subAccOut, $this->counterpart, false, $value) &&
                    $this->addTaxLine($entry, $subAccIn, $this->counterpart, true, $value);
                if (false === $done) {
                    return false;
                }
                continue;
            }

            $subAccOut = $tax->getOutputTaxAccount($this->exercise->codejercicio);
            if (false === $subAccOut->exists()) {
                Tools::log()->warning('ivarep-subaccount-not-found', [
                    '%tax%' => $tax->codimpuesto,
                    '%exercise%' => $this->exercise->codejercicio
                ]);
                return false;
            }
            $subAccOutSurcharge = $tax->getOutputSurchargeAccount($this->exercise->codejercicio);
            if (false === $subAccOutSurcharge->exists()) {
                Tools::log()->warning('ivarepre-subaccount-not-found', [
                    '%tax%' => $tax->codimpuesto,
                    '%exercise%' => $this->exercise->codejercicio
                ]);
                return false;
            }

            $done = $this->addTaxLine($entry, $subAccOut, $this->counterpart, false, $value) &&
                $this->addSurchargeLine($entry, $subAccOutSurcharge, $this->counterpart, false, $value);
            if (false === $done) {
                return false;
            }
        }
        return true;
    }

    /**
     * Añade la línea del proveedor al asiento y guarda su subcuenta como contrapartida
     * para las siguientes líneas (IVA, IRPF, mercancía).
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addSupplierLine(Asiento $entry): bool
    {
        $supplier = new Proveedor();
        if (false === $supplier->load($this->document->codproveedor)) {
            Tools::log()->warning('supplier-not-found', ['%supplier%' => $this->document->codproveedor]);
            $this->counterpart = null;
            return false;
        }

        $subAccount = $this->getSupplierAccount($supplier);
        if (false === $subAccount->exists()) {
            Tools::log()->warning('supplier-account-not-found', [
                '%supplier%' => $this->document->codproveedor,
                '%exercise%' => $this->exercise->codejercicio
            ]);
            $this->counterpart = null;
            return false;
        }

        $this->counterpart = $subAccount;
        return $this->addBasicLine($entry, $subAccount, false);
    }

    /**
     * Comprobaciones previas a la contabilización: el documento no está ya contabilizado,
     * tiene total, el ejercicio existe y está abierto, tiene plan contable, los subtotales
     * se calculan correctamente y hay cuentas dadas de alta en ese ejercicio.
     *
     * @return bool
     */
    protected function initialChecks(): bool
    {
        if (!empty($this->document->idasiento)) {
            Tools::log()->warning('document-already-accounted', ['%document%' => $this->document->codigo]);
            return false;
        }

        if (empty($this->document->total)) {
            Tools::log()->warning('document-without-total', ['%document%' => $this->document->codigo]);
            return false;
        }

        if (false === $this->exercise->load($this->document->codejercicio) || false === $this->exercise->isOpened()) {
            Tools::log()->warning('closed-exercise', ['%exerciseName%' => $this->document->codejercicio]);
            return false;
        }

        if (false === $this->exercise->hasAccountingPlan()) {
            Tools::log()->warning('exercise-without-accounting-plan', ['%exercise%' => $this->exercise->codejercicio]);
            return false;
        }

        if (false === $this->loadSubtotals()) {
            Tools::log()->warning('invoice-subtotals-error', ['%document%' => $this->document->codigo]);
            return false;
        }

        $where = [Where::eq('codejercicio', $this->document->codejercicio)];
        if (0 === Cuenta::count($where)) {
            Tools::log()->warning('accounting-data-missing', ['%exerciseName%' => $this->document->codejercicio]);
            return false;
        }

        return true;
    }

    protected function loadSubtotals(): bool
    {
        $this->subtotals = Calculator::getSubtotals($this->document, $this->document->getLines());
        return !empty($this->document->total);
    }

    /**
     * Genera el asiento contable para un documento de compra.
     */
    protected function purchaseAccountingEntry(): void
    {
        $concept = Tools::trans('supplier-invoice') . ' ' . $this->document->codigo;
        $concept .= $this->document->numproveedor ? ' (' . $this->document->numproveedor . ') - ' . $this->document->nombre :
            ' - ' . $this->document->nombre;

        $entry = new Asiento();
        $this->setAccountingData($entry, $concept);
        if (false === $entry->save()) {
            Tools::log()->warning('accounting-entry-error', ['%document%' => $this->document->codigo]);
            return;
        }

        if (false === $this->addSupplierLine($entry)) {
            Tools::log()->warning('supplier-line-error', [
                '%invoice%' => $this->document->codigo,
                '%supplier%' => $this->document->nombre
            ]);
            $entry->delete();
            return;
        }

        if (false === $this->addPurchaseTaxLines($entry)) {
            Tools::log()->warning('purchase-tax-lines-error', [
                '%invoice%' => $this->document->codigo
            ]);
            $entry->delete();
            return;
        }

        if (false === $this->addPurchaseIrpfLines($entry)) {
            Tools::log()->warning('purchase-irpf-lines-error', [
                '%invoice%' => $this->document->codigo
            ]);
            $entry->delete();
            return;
        }

        if (false === $this->addPurchaseSuppliedLines($entry)) {
            Tools::log()->warning('purchase-supplied-lines-error', [
                '%invoice%' => $this->document->codigo
            ]);
            $entry->delete();
            return;
        }

        if (false === $this->addGoodsPurchaseLine($entry)) {
            Tools::log()->warning('purchase-goods-lines-error', [
                '%invoice%' => $this->document->codigo
            ]);
            $entry->delete();
            return;
        }

        if (false === $entry->isBalanced()) {
            Tools::log()->warning('unbalanced-accounting-entry', [
                '%document%' => $entry->documento,
                '%difference%' => abs($entry->debe - $entry->haber)
            ]);
            $entry->delete();
            return;
        }

        $this->document->idasiento = $entry->id();
    }

    /**
     * Genera el asiento contable para un documento de venta.
     */
    protected function salesAccountingEntry(): void
    {
        $concept = Tools::trans('customer-invoice') . ' ' . $this->document->codigo;
        $concept .= $this->document->numero2 ? ' (' . $this->document->numero2 . ') - ' . $this->document->nombrecliente :
            ' - ' . $this->document->nombrecliente;

        $entry = new Asiento();
        $this->setAccountingData($entry, $concept);
        if (false === $entry->save()) {
            Tools::log()->warning('accounting-entry-error', ['%document%' => $this->document->codigo]);
            return;
        }

        if (false === $this->addCustomerLine($entry)) {
            Tools::log()->warning('customer-line-error', [
                '%invoice%' => $this->document->codigo,
                '%customer%' => $this->document->nombrecliente
            ]);
            $entry->delete();
            return;
        }

        if (false === $this->addSalesTaxLines($entry)) {
            Tools::log()->warning('sales-tax-lines-error', [
                '%invoice%' => $this->document->codigo
            ]);
            $entry->delete();
            return;
        }

        if (false === $this->addSalesIrpfLines($entry)) {
            Tools::log()->warning('sales-irpf-lines-error', [
                '%invoice%' => $this->document->codigo
            ]);
            $entry->delete();
            return;
        }

        if (false === $this->addSalesSuppliedLines($entry)) {
            Tools::log()->warning('sales-supplied-lines-error', [
                '%invoice%' => $this->document->codigo
            ]);
            $entry->delete();
            return;
        }

        if (false === $this->addGoodsSalesLine($entry)) {
            Tools::log()->warning('sales-goods-lines-error', [
                '%invoice%' => $this->document->codigo
            ]);
            $entry->delete();
            return;
        }

        if (false === $entry->isBalanced()) {
            Tools::log()->warning('unbalanced-accounting-entry', [
                '%document%' => $entry->documento,
                '%difference%' => abs($entry->debe - $entry->haber)
            ]);
            $entry->delete();
            return;
        }

        $this->document->idasiento = $entry->id();
    }

    /**
     * Asigna al asiento los datos básicos del documento (ejercicio, concepto, fecha,
     * empresa, importe) y copia el diario y el canal analítico desde la Serie.
     *
     * @param Asiento $entry
     * @param string $concept
     */
    protected function setAccountingData(Asiento &$entry, string $concept): void
    {
        $entry->codejercicio = $this->document->codejercicio;
        $entry->concepto = $concept;
        $entry->documento = $this->document->codigo;
        $entry->fecha = $this->document->fechadevengo ?? $this->document->fecha;
        $entry->idempresa = $this->document->idempresa;
        $entry->importe = $this->document->total;

        $serie = new Serie();
        $serie->load($this->document->codserie);

        $entry->iddiario = $serie->iddiario;
        $entry->canal = $serie->canal;
    }
}
