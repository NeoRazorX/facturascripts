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

/**
 * Controller to list the items in the AttachedFile model
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class ListAttachedFile extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'attached-files';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fas fa-paperclip';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListAttachedFile', 'AttachedFile', 'attached-files', 'fas fa-paperclip');
        $this->addSearchFields('ListAttachedFile', ['filename', 'mimetype']);
        $this->addOrderBy('ListAttachedFile', ['idfile'], 'code');
        $this->addOrderBy('ListAttachedFile', ['filename'], 'file-name');
        $this->addOrderBy('ListAttachedFile', ['size'], 'size');

        $types = $this->codeModel->all('attached_files', 'mimetype', 'mimetype');
        $this->addFilterSelect('ListAttachedFile', 'mimetype', 'mime-type', 'mimetype', $types);

        $this->addFilterDatePicker('ListAttachedFile', 'fromdate', 'from-date', 'date', '>=');
        $this->addFilterDatePicker('ListAttachedFile', 'untildate', 'until-date', 'date', '<=');
    }
}
