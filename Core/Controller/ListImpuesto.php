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
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'taxes';
        $data['icon'] = 'fas fa-plus-square';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsTax();
        $this->createViewsTaxZone();
        $this->createViewsRetention();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsRetention(string $viewName = 'ListRetencion')
    {
        $this->addView($viewName, 'Retencion', 'retentions', 'fas fa-plus-square');
        $this->addOrderBy($viewName, ['codretencion'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addSearchFields($viewName, ['descripcion', 'codretencion']);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsTax(string $viewName = 'ListImpuesto')
    {
        $this->addView($viewName, 'Impuesto', 'taxes', 'fas fa-plus-square');
        $this->addOrderBy($viewName, ['codimpuesto'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addSearchFields($viewName, ['descripcion', 'codimpuesto']);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsTaxZone(string $viewName = 'ListImpuestoZona')
    {
        $this->addView($viewName, 'ImpuestoZona', 'tax-areas', 'fas fa-globe-americas');
        $this->addOrderBy($viewName, ['prioridad'], 'priority', 2);
        $this->addOrderBy($viewName, ['codimpuesto'], 'tax');
        $this->addOrderBy($viewName, ['codpais'], 'country');
        $this->addOrderBy($viewName, ['codisopro'], 'province');
        $this->addOrderBy($viewName, ['codimpuestosel'], 'applied-tax');
        $this->addSearchFields($viewName, ['codpais']);

        /// adds generate button
        $this->addButton($viewName, [
            'action' => 'generate-zones',
            'color' => 'warning',
            'confirm' => true,
            'icon' => 'fas fa-magic',
            'label' => 'generate'
        ]);
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
                return $this->generateTaxZones();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * 
     * @return bool
     */
    protected function generateTaxZones()
    {
        $impuesto = new Impuesto();
        foreach ($impuesto->all() as $imp) {
            $impZona = new ImpuestoZona();
            $impZona->codimpuesto = $imp->codimpuesto;
            $impZona->codpais = \FS_CODPAIS;
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

        return true;
    }
}
