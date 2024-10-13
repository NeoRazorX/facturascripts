<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\BusinessDocumentCode;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the SecuenciaDocumento model.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditSecuenciaDocumento extends EditController
{
    public function getModelClassName(): string
    {
        return 'SecuenciaDocumento';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'document-sequence';
        $data['icon'] = 'fa-solid fa-code';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // desactivamos la columna de empresa si solo hay una
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        // desactivamos los botones de opciones e imprimir
        $this->setSettings($this->getMainViewName(), 'btnOptions', false);
        $this->setSettings($this->getMainViewName(), 'btnPrint', false);

        // añadimos las vistas de los documentos
        $this->createViewsDocuments('ListFacturaCliente', 'FacturaCliente', 'customer-invoices');
        $this->createViewsDocuments('ListFacturaProveedor', 'FacturaProveedor', 'supplier-invoices');
        $this->createViewsDocuments('ListAlbaranCliente', 'AlbaranCliente', 'customer-delivery-notes');
        $this->createViewsDocuments('ListAlbaranProveedor', 'AlbaranProveedor', 'supplier-delivery-notes');
        $this->createViewsDocuments('ListPedidoCliente', 'PedidoCliente', 'customer-orders');
        $this->createViewsDocuments('ListPedidoProveedor', 'PedidoProveedor', 'supplier-orders');
        $this->createViewsDocuments('ListPresupuestoCliente', 'PresupuestoCliente', 'customer-quotes');
        $this->createViewsDocuments('ListPresupuestoProveedor', 'PresupuestoProveedor', 'supplier-quotes');
    }

    protected function createViewsDocuments(string $viewName, string $model, string $title): void
    {
        $this->addListView($viewName, $model, $title, 'fa-solid fa-copy')
            ->addOrderBy(['fecha', $this->tableColToNumber('numero')], 'date', 1)
            ->addOrderBy([$this->tableColToNumber('numero')], 'number')
            ->addSearchFields(['cifnif', 'codigo', 'numero', 'observaciones']);

        // desactivamos los botones de nuevo y eliminar
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case 'ListAlbaranCliente':
            case 'ListAlbaranProveedor':
            case 'ListFacturaCliente':
            case 'ListFacturaProveedor':
            case 'ListPedidoCliente':
            case 'ListPedidoProveedor':
            case 'ListPresupuestoCliente':
            case 'ListPresupuestoProveedor':
                $where = [
                    new DataBaseWhere('codserie', $this->getViewModelValue($mvn, 'codserie')),
                    new DataBaseWhere('idempresa', $this->getViewModelValue($mvn, 'idempresa'))
                ];
                // si tiene ejercicio, solo mostramos los resultados de ese ejercicio
                if ($this->views[$mvn]->model->codejercicio) {
                    $where[] = new DataBaseWhere('codejercicio', $this->views[$mvn]->model->codejercicio);
                    $view->loadData('', $where);
                    break;
                }
                // no tiene ejercicio, mostramos los resultados otros ejercicios que no están en otras secuencias
                $other = implode(',', BusinessDocumentCode::getOtherExercises($this->views[$mvn]->model));
                if (!empty($other)) {
                    $where[] = new DataBaseWhere('codejercicio', $other, 'NOT IN');
                }
                $view->loadData('', $where);
                break;

            case $mvn:
                parent::loadData($viewName, $view);

                // desactivamos todas las pestañas de documentos
                $this->setSettings('ListAlbaranCliente', 'active', false);
                $this->setSettings('ListAlbaranProveedor', 'active', false);
                $this->setSettings('ListFacturaCliente', 'active', false);
                $this->setSettings('ListFacturaProveedor', 'active', false);
                $this->setSettings('ListPedidoCliente', 'active', false);
                $this->setSettings('ListPedidoProveedor', 'active', false);
                $this->setSettings('ListPresupuestoCliente', 'active', false);
                $this->setSettings('ListPresupuestoProveedor', 'active', false);

                // en función del tipo de documento, mostramos o no la pestaña de facturas de cliente
                if ($view->model->tipodoc) {
                    $this->setSettings('List' . $view->model->tipodoc, 'active', true);
                }
                break;
        }
    }

    private function tableColToNumber(string $name): string
    {
        return strtolower(FS_DB_TYPE) == 'postgresql' ?
            'CAST(' . $name . ' as integer)' :
            'CAST(' . $name . ' as unsigned)';
    }
}
