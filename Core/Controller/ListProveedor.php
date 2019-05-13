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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the Proveedor model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListProveedor extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'suppliers';
        $pagedata['icon'] = 'fas fa-users';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewSuppliers();
        $this->createViewAdresses();
    }

    /**
     * 
     * @param string $name
     */
    private function createViewAdresses($name = 'ListContacto')
    {
        $this->addView($name, 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $this->addSearchFields($name, ['nombre', 'apellidos', 'email']);
        $this->addOrderBy($name, ['email'], 'email');
        $this->addOrderBy($name, ['nombre'], 'name');
        $this->addOrderBy($name, ['empresa'], 'company');
        $this->addOrderBy($name, ['level'], 'level');
        $this->addOrderBy($name, ['lastactivity'], 'last-activity', 2);

        $cargoValues = $this->codeModel->all('contactos', 'cargo', 'cargo');
        $this->addFilterSelect($name, 'cargo', 'position', 'cargo', $cargoValues);

        $counties = $this->codeModel->all('paises', 'codpais', 'nombre');
        $this->addFilterSelect($name, 'codpais', 'country', 'codpais', $counties);

        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        $this->addFilterSelect($name, 'provincia', 'province', 'provincia', $provinces);

        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        $this->addFilterSelect($name, 'ciudad', 'city', 'ciudad', $cities);

        $this->addFilterCheckbox($name, 'verificado', 'verified', 'verificado');
        $this->addFilterCheckbox($name, 'admitemarketing', 'allow-marketing', 'admitemarketing');

        /// disable megasearch
        $this->setSettings($name, 'megasearch', false);
    }

    /**
     * 
     * @param string $name
     */
    private function createViewSuppliers($name = 'ListProveedor')
    {
        $this->addView($name, 'Proveedor', 'suppliers', 'fas fa-users');
        $this->addSearchFields($name, ['cifnif', 'codproveedor', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2']);
        $this->addOrderBy($name, ['codproveedor'], 'code');
        $this->addOrderBy($name, ['nombre'], 'name', 1);
        $this->addOrderBy($name, ['fecha'], 'date');

        $values = [
            ['label' => $this->i18n->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => $this->i18n->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => $this->i18n->trans('all'), 'where' => []]
        ];
        $this->addFilterSelectWhere($name, 'status', $values);
    }
}
