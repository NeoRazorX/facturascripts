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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the Serie model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Artex Trading sa         <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class EditSerie extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Serie';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'serie';
        $data['icon'] = 'fas fa-layer-group';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createFormatView(string $viewName = 'ListFormatoDocumento')
    {
        $this->addListView($viewName, 'FormatoDocumento', 'printing-format', 'fas fa-print');
        $this->views[$viewName]->addOrderBy(['tipodoc'], 'doc-type', 2);

        /// disable columns
        $this->views[$viewName]->disableColumn('serie');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createSequenceView(string $viewName = 'ListSecuenciaDocumento')
    {
        $this->addListView($viewName, 'SecuenciaDocumento', 'sequences', 'fas fa-code');
        $this->views[$viewName]->addOrderBy(['codejercicio', 'tipodoc'], 'exercise', 2);

        /// disable columns
        $this->views[$viewName]->disableColumn('serie');
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createSequenceView();
        $this->createFormatView();
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListFormatoDocumento':
            case 'ListSecuenciaDocumento':
                $codserie = $this->getViewModelValue($this->getMainViewName(), 'codserie');
                $where = [new DataBaseWhere('codserie', $codserie)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
