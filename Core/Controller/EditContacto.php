<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Contacto;

/**
 * Controller to edit a single item from the Contacto model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class EditContacto extends ExtendedController\EditController
{

    /**
     * 
     * @return string
     */
    public function getImageUrl()
    {
        return $this->views['EditContacto']->model->gravatar();
    }

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Contacto';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'contact';
        $pagedata['menu'] = 'sales';
        $pagedata['icon'] = 'fas fa-address-book';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Run the controller after actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'convert-to-customer':
                $code = $this->request->request->get('code', '');
                $contact = new Contacto();
                $contact->loadFromCode($code);

                $customer = $contact->getCustomer();
                if(empty($customer->codcliente)) {
                    $this->miniLog->error($this->i18n->trans('problem-storage-customer'));
                } else {
                    $this->miniLog->info($this->i18n->trans('customer-created'));
                }
                break;
        }
    }
}
