<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\BusinessDocumentView;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of PurchaseDocumentController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PurchaseDocumentController extends BusinessDocumentController
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
                'label' => 'numsupplier',
                'name' => 'numproveedor'
            ]
        ];
    }

    /**
     * 
     * @return string
     */
    public function getNewSubjectUrl()
    {
        $proveedor = new Proveedor();
        return $proveedor->url('new') . '?return=' . $this->url();
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
     * 
     * @return string
     */
    protected function getLineXMLView()
    {
        return 'PurchaseDocumentLine';
    }

    /**
     * 
     * @param BusinessDocumentView $view
     * @param array                $formData
     * 
     * @return string
     */
    protected function setSubject(&$view, $formData)
    {
        if (empty($formData['codproveedor'])) {
            return 'ERROR: ' . $this->toolBox()->i18n()->trans('supplier-not-found');
        }

        if ($view->model->codproveedor === $formData['codproveedor']) {
            return 'OK';
        }

        $proveedor = new Proveedor();
        if ($proveedor->loadFromCode($formData['codproveedor'])) {
            $view->model->setSubject($proveedor);
            return 'OK';
        }

        return 'ERROR: ' . $this->toolBox()->i18n()->trans('supplier-not-found');
    }
}
