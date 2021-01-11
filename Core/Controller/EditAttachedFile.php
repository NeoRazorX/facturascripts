<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Controller to edit a single item from the AttachedFile model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class EditAttachedFile extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'AttachedFile';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'attached-file';
        $data['icon'] = 'fas fa-paperclip';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewsPreview();
        $this->createViewsRelations();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsPreview(string $viewName = 'preview')
    {
        $this->addHtmlView($viewName, 'Tab/AttachedFilePreview', 'AttachedFile', 'file', 'fas fa-eye');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsRelations(string $viewName = 'ListAttachedFileRelation')
    {
        $this->addListView($viewName, 'AttachedFileRelation', 'related', 'fas fa-copy');
        $this->views[$viewName]->addSearchFields(['observations']);
        $this->views[$viewName]->addOrderBy(['creationdate'], 'date', 2);
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListAttachedFileRelation':
                $idfile = $this->getViewModelValue($this->getMainViewName(), 'idfile');
                $where = [new DataBaseWhere('idfile', $idfile)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
