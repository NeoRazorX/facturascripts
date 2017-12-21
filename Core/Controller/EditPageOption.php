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
 * Edit option for any page.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditPageOption extends ExtendedController\EditController
{
    /**
     * Initializes all the objects and properties
     *
     * @param Base\Cache $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog $miniLog
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);
        $this->setTemplate('EditPageOption');
    }

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return '\FacturaScripts\Dinamic\Model\PageOption';
    }

    /**
     * Load data of view from code
     *
     * @param string|array $code
     */
    protected function loadData($code)
    {
        $keys = [ 'name' => $code, 'nick' => $this->user->nick];
        parent::loadData($keys);
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'page-configuration';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fa-wrench';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Returns the text for the data main panel header
     *
     * @return string
     */
    public function getPanelHeader()
    {
        return $this->i18n->trans('configure-columns');
    }

    /**
     * Returns the text for the data main panel footer
     *
     * @return string
     */
    public function getPanelFooter()
    {
        $model = $this->getModel();
        return '<strong>'
            . $this->i18n->trans('page') . ':&nbsp;' . $model->name . '<br>'
            . $this->i18n->trans('user') . ':&nbsp;' . $model->nick
            . '</strong>';
    }
}
