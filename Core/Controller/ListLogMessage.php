<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\LogMessage;

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
     * @param string $name
     */
    protected function createCronJobView($name = 'ListCronJob')
    {
        $this->addView($name, 'CronJob', 'crons', 'fas fa-cogs');
        $this->addSearchFields($name, ['jobname', 'pluginname']);
        $this->addOrderBy($name, ['jobname'], 'job-name');
        $this->addOrderBy($name, ['pluginname'], 'plugin');
        $this->addOrderBy($name, ['date'], 'date');

        /// filters
        $this->addFilterDatePicker($name, 'fromdate', 'from-date', 'date', '>=');
        $this->addFilterDatePicker($name, 'untildate', 'until-date', 'date', '<=');

        $plugins = $this->codeModel->all('cronjobs', 'pluginname', 'pluginname');
        $this->addFilterSelect($name, 'pluginname', 'plugin', 'pluginname', $plugins);

        /// settings
        $this->setSettings($name, 'btnNew', false);
    }

    /**
     * 
     * @param string $name
     */
    protected function createEmailSentView($name = 'ListEmailSent')
    {
        $this->addView($name, 'EmailSent', 'emails-sent', 'fas fa-envelope');
        $this->addOrderBy($name, ['date'], 'date', 2);
        $this->addSearchFields($name, ['subject', 'text', 'addressee']);

        /// filters
        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->addFilterSelect($name, 'nick', 'user', 'nick', $users);

        /// settings
        $this->setSettings($name, 'btnNew', false);
    }

    /**
     * Create view to get information about all logs.
     * 
     * @param string $name
     */
    protected function createLogMessageView($name = 'ListLogMessage')
    {
        $this->addView($name, 'LogMessage', 'logs', 'fas fa-file-medical-alt');
        $this->addSearchFields($name, ['message', 'uri']);
        $this->addOrderBy($name, ['time'], 'date', 2);
        $this->addOrderBy($name, ['level'], 'level');

        /// filters
        $levels = $this->codeModel->all('logs', 'level', 'level');
        $this->addFilterSelect($name, 'level', 'level', 'level', $levels);

        $this->addFilterAutocomplete($name, 'nick', 'user', 'nick', 'users');
        $this->addFilterAutocomplete($name, 'ip', 'ip', 'ip', 'logs');

        $uris = $this->codeModel->all('logs', 'uri', 'uri');
        $this->addFilterSelect($name, 'url', 'url', 'uri', $uris);

        $this->addFilterDatePicker($name, 'fromdate', 'from-date', 'time', '>=');
        $this->addFilterDatePicker($name, 'untildate', 'until-date', 'time', '<=');

        /// settings
        $this->setSettings($name, 'btnNew', false);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'delete-selected-filters':
                $this->deleteWithFilters();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Delete logs based on active filters.
     */
    protected function deleteWithFilters()
    {
        // start transaction
        $this->dataBase->beginTransaction();

        try {
            $logMessage = new LogMessage();

            $this->views['ListLogMessage']->processFormData($this->request, 'load');
            $where = $this->views['ListLogMessage']->where;
            $allFilteredLogs = $logMessage->all($where, [], 0, 0);
            $counter = 0;
            foreach ($allFilteredLogs as $log) {
                if (!$log->delete()) {
                    $this->miniLog->warning($this->i18n->trans('record-deleted-error'));
                    break;
                }

                $counter++;
            }

            // confirm data
            $this->dataBase->commit();

            if ($counter > 0) {
                $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
            }
        } catch (Exception $exc) {
            $this->miniLog->alert($exc->getMessage());
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }
    }
}
