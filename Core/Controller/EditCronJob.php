<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of EditCronJob
 *
 * @author Raul Jimenez         <raljopa@gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class EditCronJob extends EditController
{
    public function getModelClassName(): string
    {
        return 'CronJob';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'cron-job';
        $data['icon'] = 'fa-solid fa-cogs';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();

        // desactivamos los botones nuevo y opciones
        $mvn = $this->getMainViewName();
        $this->setSettings($mvn, 'btnNew', false);
        $this->setSettings($mvn, 'btnOptions', false);

        // añadimos la pestaña de logs
        $this->createViewsLogs();

        // colocamos las pestañas abajo
        $this->setTabsPosition('bottom');
    }

    protected function createViewsLogs(string $viewName = 'ListLogMessage')
    {
        $this->addListView($viewName, 'LogMessage', 'related', 'fa-solid fa-file-medical-alt');
        $this->views[$viewName]->addSearchFields(['ip', 'message', 'uri']);
        $this->views[$viewName]->addOrderBy(['time', 'id'], 'date', 2);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListLogMessage':
                $name = $this->getViewModelValue($this->getMainViewName(), 'jobname');
                $where = [new DataBaseWhere('channel', $name)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
