<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\FacturaCliente;

/**
 * Controller to edit a single item from the FacturaCliente model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Luis Miguel Pérez        <luismi@pcrednet.com>
 * @author Rafael San José Tovar    <rafael.sanjose@x-netdigital.com>
 */
class EditFacturaCliente extends ExtendedController\SalesDocumentController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'invoice';
        $pagedata['menu'] = 'sales';
        $pagedata['icon'] = 'fas fa-copy';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();

        $this->addListView('ListAsiento', 'Asiento', 'accounting-entries', 'fas fa-balance-scale');
        $this->setSettings('ListAsiento', 'btnNew', false);

        $this->addHtmlView('Devoluciones', 'Tab/DevolucionesFacturaCliente', 'FacturaCliente', 'refunds', 'fas fa-share-square');
    }

    /**
     * 
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'new-refund':
                $this->newRefundAction();
                break;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Return the document class name.
     *
     * @return string
     */
    protected function getModelClassName()
    {
        return 'FacturaCliente';
    }

    /**
     * Load data view procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'Devoluciones':
                $where = [new DataBaseWhere('idfactura', $this->getViewModelValue($this->getLineXMLView(), 'idfactura'))];
                $view->loadData('', $where);
                break;

            case 'ListAsiento':
                $where = [
                    new DataBaseWhere('idasiento', $this->getViewModelValue($this->getLineXMLView(), 'idasiento')),
                    new DataBaseWhere('idasiento', $this->getViewModelValue($this->getLineXMLView(), 'idasientop'), '=', 'OR')
                ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }

    protected function newRefundAction()
    {
        $invoice = new FacturaCliente();
        if (!$invoice->loadFromCode($this->request->request->get('idfactura'))) {
            $this->miniLog->warning($this->i18n->trans('record-not-found'));
            return;
        }

        $lines = [];
        $quantities = [];
        foreach ($invoice->getLines() as $line) {
            $quantity = (float) $this->request->request->get('refund_' . $line->primaryColumnValue(), '0');
            if (empty($quantity)) {
                continue;
            }

            $quantities[$line->primaryColumnValue()] = 0 - $quantity;
            $lines[] = $line;
        }

        $generator = new BusinessDocumentGenerator();
        if ($generator->generate($invoice, $invoice->modelClassName(), $lines, $quantities)) {
            foreach ($generator->getLastDocs() as $doc) {
                $doc->codigorect = $invoice->codigo;
                $doc->codserie = $this->request->request->get('codserie');
                $doc->fecha = $this->request->request->get('fecha');
                $doc->idfacturarect = $invoice->idfactura;
                $doc->observaciones = $this->request->request->get('observaciones');
                if ($doc->save()) {
                    $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
                    $this->response->headers->set('Refresh', '0; ' . $doc->url());
                    continue;
                }

                $this->miniLog->error($this->i18n->trans('record-save-error'));
            }

            return;
        }

        $this->miniLog->error($this->i18n->trans('record-save-error'));
    }
}
