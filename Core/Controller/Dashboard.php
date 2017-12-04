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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;
use FacturaScripts\Core\Lib;

/**
 * Description of Dashboard
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Dashboard extends Base\Controller
{

    /**
     * List of components of dashboard.
     *
     * @var BaseComponent[]
     */
    public $components;

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->components = [];
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        $this->getListComponents($user->nick);
        $this->loadDataComponents();
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'dashboard';
        $pageData['menu'] = 'reports';
        $pageData['icon'] = 'fa-dashboard';

        return $pageData;
    }

    private function getListComponents($userNick)
    {
        $dashboardModel = new Model\Dashboard();
        $rows = $dashboardModel->all();
        foreach ($rows as $data) {
            $componentName = Lib\Dashboard\BaseComponent::DIR_COMPONENTS
                . $data->component
                . Lib\Dashboard\BaseComponent::SUFIX_COMPONENTS;
            
            $this->components[$data->component] = new $componentName($data, $userNick);
        }
    }

    private function loadDataComponents()
    {
        foreach ($this->components as $component) {
            $component->loadData();
        }
    }
}
