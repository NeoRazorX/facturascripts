<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Model\LogMessage;

/**
 * Controller to list the items in the LogMessage model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra      <francesc.pineda.segarra@gmail.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListLogMessage extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'logs';
        $pagedata['icon'] = 'fas fa-file-medical-alt';
        $pagedata['menu'] = 'admin';
        $pagedata['submenu'] = 'control-panel';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createLogMessageView();
        $this->createCronJobView();
    }

    /**
     * Create view to view all information about crons.
     *
     * @return void
     */
    private function createCronJobView()
    {
        $this->addView('ListCronJob', 'CronJob', 'crons', 'fas fa-cogs');
        $this->addSearchFields('ListCronJob', ['jobname', 'pluginname']);
        $this->addOrderBy('ListCronJob', ['jobname'], 'jobname');
        $this->addOrderBy('ListCronJob', ['pluginname'], 'pluginname');
        $this->addOrderBy('ListCronJob', ['date'], 'date');
        $this->addFilterDatePicker('ListCronJob', 'fromdate', 'from-date', 'date', '>=');
        $this->addFilterDatePicker('ListCronJob', 'untildate', 'until-date', 'date', '<=');

        $this->setSettings('ListCronJob', 'btnNew', false);
    }

    /**
     * Create view to get information about all logs.
     *
     * @return void
     */
    private function createLogMessageView()
    {
        $this->addView('ListLogMessage', 'LogMessage', 'logs', 'fas fa-file-medical-alt');
        $this->addSearchFields('ListLogMessage', ['message', 'uri']);
        $this->addOrderBy('ListLogMessage', ['time'], 'date', 2);
        $this->addOrderBy('ListLogMessage', ['level'], 'level');

        $values = $this->codeModel->all('logs', 'level', 'level');
        $this->addFilterSelect('ListLogMessage', 'level', 'level', 'level', $values);
        $this->addFilterAutocomplete('ListLogMessage', 'nick', 'user', 'nick', 'users');
        $this->addFilterAutocomplete('ListLogMessage', 'ip', 'ip', 'ip', 'logs');
        $this->addFilterDatePicker('ListLogMessage', 'fromdate', 'from-date', 'time', '>=');
        $this->addFilterDatePicker('ListLogMessage', 'untildate', 'until-date', 'time', '<=');

        $this->setSettings('ListLogMessage', 'btnNew', false);
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
    private function deleteWithFilters()
    {
        // start transaction
        $this->dataBase->beginTransaction();

        // main save process
        try {
            $logMessage = new LogMessage();

            $this->views['ListLogMessage']->processFormData($this->request, 'load');
            $where = $this->views['ListLogMessage']->where;
            $allFilteredLogs = $logMessage->all($where, [], 0, 0);
            $counter = 0;
            foreach ($allFilteredLogs as $log) {
                if ($log->delete()) {
                    $counter++;
                } else {
                    $this->miniLog->alert('cant-delete-item', ['%modelName%' => 'LogMessage', '%code%' => $log->primaryColumnValue()]);
                    break;
                }
            }
            // confirm data
            $this->dataBase->commit();
            if ($counter > 0) {
                $this->miniLog->notice('total-items-deleted', ['%total%' => $counter]);
            }
        } catch (\Exception $e) {
            $this->miniLog->alert($e->getMessage());
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }
    }
}
