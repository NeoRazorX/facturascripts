<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Controller to list the items in the LogMessage model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra      <francesc.pineda.segarra@gmail.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListLogMessage extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['submenu'] = 'control-panel';
        $data['title'] = 'logs';
        $data['icon'] = 'fas fa-file-medical-alt';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createLogMessageView();
        $this->createCronJobView();
        $this->createEmailSentView();
    }

    /**
     * Create view to view all information about crons.
     * 
     * @param string $viewName
     */
    protected function createCronJobView(string $viewName = 'ListCronJob')
    {
        $this->addView($viewName, 'CronJob', 'crons', 'fas fa-cogs');
        $this->addSearchFields($viewName, ['jobname', 'pluginname']);
        $this->addOrderBy($viewName, ['jobname'], 'job-name');
        $this->addOrderBy($viewName, ['pluginname'], 'plugin');
        $this->addOrderBy($viewName, ['date'], 'date');
        $this->addOrderBy($viewName, ['duration'], 'duration');

        /// filters
        $plugins = $this->codeModel->all('cronjobs', 'pluginname', 'pluginname');
        $this->addFilterSelect($viewName, 'pluginname', 'plugin', 'pluginname', $plugins);

        $this->addFilterPeriod($viewName, 'date', 'period', 'date');

        /// settings
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createEmailSentView(string $viewName = 'ListEmailSent')
    {
        $this->addView($viewName, 'EmailSent', 'emails-sent', 'fas fa-envelope');
        $this->addOrderBy($viewName, ['date'], 'date', 2);
        $this->addSearchFields($viewName, ['addressee', 'body', 'subject']);

        /// filters
        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->addFilterSelect($viewName, 'nick', 'user', 'nick', $users);
        $this->addFilterPeriod($viewName, 'date', 'period', 'date');
        $this->addFilterCheckbox($viewName, 'opened');

        /// settings
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Create view to get information about all logs.
     * 
     * @param string $viewName
     */
    protected function createLogMessageView(string $viewName = 'ListLogMessage')
    {
        $this->addView($viewName, 'LogMessage', 'logs', 'fas fa-file-medical-alt');
        $this->addSearchFields($viewName, ['message', 'uri']);
        $this->addOrderBy($viewName, ['time', 'id'], 'date', 2);
        $this->addOrderBy($viewName, ['level'], 'level');

        /// filters
        $channels = $this->codeModel->all('logs', 'channel', 'channel');
        $this->addFilterSelect($viewName, 'channel', 'channel', 'channel', $channels);

        $levels = $this->codeModel->all('logs', 'level', 'level');
        $this->addFilterSelect($viewName, 'level', 'level', 'level', $levels);

        $this->addFilterAutocomplete($viewName, 'nick', 'user', 'nick', 'users');
        $this->addFilterAutocomplete($viewName, 'ip', 'ip', 'ip', 'logs');

        $uris = $this->codeModel->all('logs', 'uri', 'uri');
        $this->addFilterSelect($viewName, 'url', 'url', 'uri', $uris);

        $this->addFilterPeriod($viewName, 'time', 'period', 'time');

        /// settings
        $this->setSettings($viewName, 'btnNew', false);
    }
}
