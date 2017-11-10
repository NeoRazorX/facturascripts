<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of ListAsiento
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListAsiento extends ExtendedController\ListController
{

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $this->addView('FacturaScripts\Core\Model\Asiento', 'ListAsiento');
        $this->addSearchFields('ListAsiento', ['numero', 'concepto']);

        $this->addFilterDatePicker('ListAsiento', 'date', 'date', 'fecha');
        $this->addFilterNumber('ListAsiento', 'ammount', 'ammount', 'importe');
        $this->addFilterSelect('ListAsiento', 'codejercicio', 'ejercicios', '', 'nombre');

        $this->addOrderBy('ListAsiento', 'numero', 'number');
        $this->addOrderBy('ListAsiento', 'fecha', 'date', 2); /// forzamos el orden por defecto fecha desc
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'accounting-entries';
        $pagedata['icon'] = 'fa-balance-scale';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }
}
