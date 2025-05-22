<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;

/**
 * Controller to edit a single item from the Serie model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Francesc Pineda Segarra       <francesc.pineda.segarra@gmail.com>
 */
class EditSerie extends EditController
{
    public function getModelClassName(): string
    {
        return 'Serie';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'serie';
        $data['icon'] = 'fa-solid fa-layer-group';
        return $data;
    }

    protected function createFormatView(string $viewName = 'ListFormatoDocumento')
    {
        $this->addListView($viewName, 'FormatoDocumento', 'printing-format', 'fa-solid fa-print');
        $this->views[$viewName]->addOrderBy(['tipodoc'], 'doc-type', 2);

        // desactivamos la columna serie
        $this->views[$viewName]->disableColumn('serie');
    }

    protected function createSequenceView(string $viewName = 'ListSecuenciaDocumento')
    {
        $this->addListView($viewName, 'SecuenciaDocumento', 'sequences', 'fa-solid fa-code');
        $this->views[$viewName]->addOrderBy(['codejercicio', 'tipodoc'], 'exercise');
        $this->views[$viewName]->addOrderBy(['tipodoc', 'codejercicio'], 'doc-type', 1);
        $this->views[$viewName]->addSearchFields(['patron', 'tipodoc']);

        // desactivamos la columna serie
        $this->views[$viewName]->disableColumn('serie');

        // desactivamos la columna empresa si solo hay una
        if ($this->empresa->count() < 2) {
            $this->views[$viewName]->disableColumn('company');
        }

        // filtros
        $types = $this->codeModel->all('estados_documentos', 'tipodoc', 'tipodoc');
        foreach ($types as $value) {
            if (!empty($value->code)) {
                $value->description = Tools::lang()->trans($value->code);
            }
        }
        $this->views[$viewName]->addFilterSelect('tipodoc', 'doc-type', 'tipodoc', $types);
        $this->views[$viewName]->addFilterSelect('codejercicio', 'exercise', 'codejercicio', Ejercicios::codeModel());
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
     * @param string $viewName
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
