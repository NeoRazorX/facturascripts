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
use FacturaScripts\Core\Where;

/**
 * Controlador para editar un único elemento del modelo Diario
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 */
class EditDiario extends EditController
{
    public function getModelClassName(): string
    {
        return 'Diario';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'journal';
        $data['icon'] = 'fa-solid fa-book';
        return $data;
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewsEntries();
        $this->setTabsPosition('bottom');
    }

    protected function createViewsEntries(string $viewName = 'ListAsiento')
    {
        $this->addListView($viewName, 'Asiento', 'accounting-entry');
        $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['importe'], 'amount');
        $this->views[$viewName]->addSearchFields(['concepto']);

        // disable columns
        $this->views[$viewName]->disableColumn('journal');

        // disable button
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListAsiento':
                $id = $this->mainTabModelValue('iddiario');
                $where = [Where::eq('iddiario', $id)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
