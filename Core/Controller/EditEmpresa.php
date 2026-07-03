<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Core\Lib\PDF\Dynamic\PDFBuilder;
use FacturaScripts\Core\Lib\PDF\Dynamic\PDFPreviewTrait;
use FacturaScripts\Dinamic\Lib\RegimenIVA;

/**
 * Controller to edit a single item from the  Empresa model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Daniel Fernández Giménez      <contacto@danielfg.es>
 */
class EditEmpresa extends EditController
{
    use PDFPreviewTrait;

    public function getModelClassName(): string
    {
        return 'Empresa';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'company';
        $data['icon'] = 'fa-solid fa-building';
        return $data;
    }

    protected function buildPdf(): PDFBuilder
    {
        $empresa = $this->getModel();
        $empresa->loadFromCode($this->request->input('code'));
        $i18n = Tools::lang();

        $doc = PDFBuilder::create()
            ->setTitle($empresa->nombrecorto ?? $empresa->nombre ?? 'company')
            ->addDocumentHeader($empresa)
            ->addTitle($i18n->trans('company') . ': ' . $empresa->nombre)
            ->addHtml('<hr/>')
            ->addParallelTable([
                $i18n->trans('name') => $empresa->nombre,
                $i18n->trans('short-name') => $empresa->nombrecorto,
                $i18n->trans('fiscal-id') => $empresa->tipoidfiscal,
                $i18n->trans('fiscal-number') => $empresa->cifnif,
                $i18n->trans('address') => $empresa->direccion,
                $i18n->trans('zip-code') => $empresa->codpostal,
                $i18n->trans('city') => $empresa->ciudad,
                $i18n->trans('province') => $empresa->provincia,
                $i18n->trans('country') => $empresa->codpais,
                $i18n->trans('phone') => $empresa->telefono1,
                $i18n->trans('phone2') => $empresa->telefono2,
                $i18n->trans('fax') => $empresa->fax,
                $i18n->trans('email') => $empresa->email,
                $i18n->trans('web') => $empresa->web,
                $i18n->trans('admin') => $empresa->administrador,
                $i18n->trans('start-date') => $empresa->fechaalta,
            ]);

        // las vistas relacionadas, como hace el export original con cada pestaña
        foreach (['EditAlmacen', 'ListCuentaBanco', 'ListFormaPago', 'ListEjercicio'] as $viewName) {
            $view = $this->views[$viewName] ?? null;
            if (null === $view) {
                continue;
            }

            $modelClass = get_class($view->model);
            $cursor = $modelClass::all([Where::eq('idempresa', $empresa->idempresa)]);
            if (empty($cursor)) {
                continue;
            }

            $doc->addSpacer(3)
                ->addTitle($view->title, 2)
                ->addModelTable($cursor, $view->getColumns());
        }

        return $doc->addPageFooter('1 / 1', $i18n->trans('generated-at', ['%when%' => Tools::dateTime()]));
    }

    protected function checkViesAction(): bool
    {
        $model = $this->getModel();
        $code = $this->request->input('code');
        if (false === $model->loadFromCode($code)) {
            return true;
        }

        if ($model->checkVies()) {
            Tools::log()->notice('vies-check-success', ['%vat-number%' => $model->cifnif]);
        }

        return true;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->loadPdfViewerAssets();

        $this->createViewWarehouse();
        $this->createViewBankAccounts();
        $this->createViewPaymentMethods();
        $this->createViewExercises();
    }

    protected function createViewBankAccounts(string $viewName = 'ListCuentaBanco'): void
    {
        $this->addListView($viewName, 'CuentaBanco', 'bank-accounts', 'fa-solid fa-piggy-bank')
            ->disableColumn('company');
    }

    protected function createViewExercises(string $viewName = 'ListEjercicio'): void
    {
        $this->addListView($viewName, 'Ejercicio', 'exercises', 'fa-solid fa-calendar-alt')
            ->disableColumn('company');
    }

    protected function createViewPaymentMethods(string $viewName = 'ListFormaPago'): void
    {
        $this->addListView($viewName, 'FormaPago', 'payment-method', 'fa-solid fa-credit-card')
            ->disableColumn('company');
    }

    protected function createViewWarehouse(string $viewName = 'EditAlmacen'): void
    {
        $this->addListView($viewName, 'Almacen', 'warehouses', 'fa-solid fa-warehouse')
            ->disableColumn('company');
    }

    protected function execPreviousAction($action): bool
    {
        switch ($action) {
            case 'check-vies':
                return $this->checkViesAction();

            case 'pdf-preview':
                return $this->pdfPreviewAction();

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case 'EditAlmacen':
            case 'ListCuentaBanco':
            case 'ListEjercicio':
            case 'ListFormaPago':
                $id = $this->getViewModelValue($this->getMainViewName(), 'idempresa');
                $where = [Where::eq('idempresa', $id)];
                $view->loadData('', $where);
                break;

            case $mvn:
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($view);
                if ($view->model->exists() && $view->model->cifnif) {
                    $this->addButton($viewName, [
                        'action' => 'check-vies',
                        'color' => 'info',
                        'icon' => 'fa-solid fa-check-double',
                        'label' => 'check-vies'
                    ]);
                }
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function setCustomWidgetValues(BaseView &$view): void
    {
        $columnVATType = $view->columnForName('vat-regime');
        if ($columnVATType && $columnVATType->widget->getType() === 'select') {
            $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all(), true);
        }
    }
}
