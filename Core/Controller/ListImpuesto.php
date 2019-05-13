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

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\ImpuestoZona;

/**
 * Controller to list the items in the Impuesto model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Artex Trading sa         <jcuello@artextrading.com>
 * @author Rafael San José Tovar    <rafael.sanjose@x-netdigital.com>
 */
class ListImpuesto extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['menu'] = 'accounting';
        $pagedata['submenu'] = 'taxes';
        $pagedata['title'] = 'taxes';
        $pagedata['icon'] = 'fas fa-plus-square';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewTax();
        $this->createViewTaxZone();
        $this->createViewRetention();
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewRetention($name = 'ListRetencion')
    {
        $this->addView($name, 'Retencion', 'retentions', 'fas fa-plus-square');
        $this->addSearchFields($name, ['descripcion', 'codretencion']);
        $this->addOrderBy($name, ['codretencion'], 'code');
        $this->addOrderBy($name, ['descripcion'], 'description');
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewTax($name = 'ListImpuesto')
    {
        $this->addView($name, 'Impuesto', 'taxes', 'fas fa-plus-square');
        $this->addSearchFields($name, ['descripcion', 'codimpuesto']);
        $this->addOrderBy($name, ['codimpuesto'], 'code');
        $this->addOrderBy($name, ['descripcion'], 'description');
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewTaxZone($name = 'ListImpuestoZona')
    {
        $this->addView($name, 'ImpuestoZona', 'tax-areas', 'fas fa-globe-americas');
        $this->addSearchFields($name, ['codpais']);
        $this->addOrderBy($name, ['prioridad'], 'priority', 2);
        $this->addOrderBy($name, ['codimpuesto'], 'tax');
        $this->addOrderBy($name, ['codpais'], 'country');
        $this->addOrderBy($name, ['codisopro'], 'province');
        $this->addOrderBy($name, ['codimpuestosel'], 'applied-tax');

        /// buttons
        $button = [
            'action' => 'generate-zones',
            'color' => 'warning',
            'icon' => 'fas fa-magic',
            'label' => 'generate',
            'type' => 'action'
        ];
        $this->addButton($name, $button);
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
            case 'generate-zones':
                $this->generateTaxZones();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    protected function generateTaxZones()
    {
        $impuesto = new Impuesto();
        foreach ($impuesto->all() as $imp) {
            $impZona = new ImpuestoZona();
            $impZona->codimpuesto = $imp->codimpuesto;
            $impZona->codpais = FS_CODPAIS;
            $impZona->codisopro = null;
            $impZona->codimpuestosel = $imp->codimpuesto;
            $impZona->prioridad = 1;
            $impZona->save();

            $impZona2 = new ImpuestoZona();
            $impZona2->codimpuesto = $imp->codimpuesto;
            $impZona2->codpais = null;
            $impZona2->codisopro = null;
            $impZona2->prioridad = 0;
            $impZona2->save();
        }
    }
}
