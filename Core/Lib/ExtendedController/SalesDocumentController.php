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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Description of SalesDocumentController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class SalesDocumentController extends BusinessDocumentController
{

    /**
     * 
     * @return array
     */
    public function getCustomFields()
    {
        return [
            [
                'icon' => 'fas fa-hashtag',
                'label' => 'number2',
                'name' => 'numero2'
            ]
        ];
    }

    /**
     * 
     * @return string
     */
    public function getNewSubjectUrl()
    {
        $cliente = new Cliente();
        return $cliente->url('new');
    }

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Loads custom contact data for additional address details.
     *
     * @param mixed $view
     */
    protected function loadCustomContactsWidget(&$view)
    {
        $cliente = new Cliente();
        if (!$cliente->loadFromCode($view->model->codcliente)) {
            return;
        }

        $addresses = [];
        foreach ($cliente->getAdresses() as $contacto) {
            $addresses[] = ['value' => $contacto->idcontacto, 'title' => $contacto->nombre];
        }

        /// billing address
        $columnBilling = $view->columnForName('billingaddr');
        $columnBilling->widget->setValuesFromArray($addresses, false);

        /// shipping address
        $columnShipping = $view->columnForName('shippingaddr');
        $columnShipping->widget->setValuesFromArray($addresses, false, true);
    }

    /**
     * 
     * @param mixed $view
     * @param array $formData
     *
     * @return string
     */
    protected function setSubject(&$view, $formData)
    {
        if (empty($formData['codcliente'])) {
            return 'ERROR: ' . $this->i18n->trans('customer-not-found');
        }

        if ($view->model->codcliente === $formData['codcliente']) {
            return 'OK';
        }

        $cliente = new Cliente();
        if ($cliente->loadFromCode($formData['codcliente'])) {
            $view->model->setSubject($cliente);
            return 'OK';
        }

        return 'ERROR: ' . $this->i18n->trans('customer-not-found');
    }
}
