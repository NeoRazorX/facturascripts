<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CronJob;
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
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'logs';
        $data['icon'] = 'fa-solid fa-file-medical-alt';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsLogs();
        $this->createViewsCronJobs();
        $this->createViewsWorkEvents();
    }

    protected function createViewsCronJobs(string $viewName = 'ListCronJob'): void
    {
        $plugins = $this->codeModel->all('cronjobs', 'pluginname', 'pluginname');

        $this->addView($viewName, 'CronJob', 'crons', 'fa-solid fa-cogs')
            ->addSearchFields(['jobname', 'pluginname'])
            ->addOrderBy(['jobname'], 'job-name')
            ->addOrderBy(['pluginname'], 'plugin')
            ->addOrderBy(['date'], 'date')
            ->addOrderBy(['duration'], 'duration')
            ->setSettings('btnNew', false)
            ->addFilterPeriod('date', 'period', 'date', true)
            ->addFilterSelect('pluginname', 'plugin', 'pluginname', $plugins)
            ->addFilterSelect('enabled', 'status', 'enabled', [
                '' => '------',
                '0' => Tools::trans('disabled'),
                '1' => Tools::trans('enabled'),
            ]);

        // añadimos los botones de activar y desactivar
        $this->addButton($viewName, [
            'action' => 'enable-cronjob',
            'color' => 'success',
            'icon' => 'fa-solid fa-check-square',
            'label' => 'enable'
        ]);

        $this->addButton($viewName, [
            'action' => 'disable-cronjob',
            'color' => 'warning',
            'icon' => 'fa-regular fa-square',
            'label' => 'disable'
        ]);
    }

    protected function createViewsLogs(string $viewName = 'ListLogMessage'): void
    {
        $channels = $this->codeModel->all('logs', 'channel', 'channel');
        $levels = $this->codeModel->all('logs', 'level', 'level');
        $uris = $this->codeModel->all('logs', 'uri', 'uri');
        $models = $this->codeModel->all('logs', 'model', 'model');

        $this->addView($viewName, 'LogMessage', 'history', 'fa-solid fa-history')
            ->addSearchFields(['context', 'message', 'uri'])
            ->addOrderBy(['time', 'id'], 'date', 2)
            ->addOrderBy(['level'], 'level')
            ->addOrderBy(['ip'], 'ip')
            ->setSettings('btnNew', false)
            ->addFilterSelect('channel', 'channel', 'channel', $channels)
            ->addFilterSelect('level', 'level', 'level', $levels)
            ->addFilterAutocomplete('nick', 'user', 'nick', 'users')
            ->addFilterAutocomplete('ip', 'ip', 'ip', 'logs')
            ->addFilterSelect('url', 'url', 'uri', $uris)
            ->addFilterSelect('model', 'doc-type', 'model', $models)
            ->addFilterPeriod('time', 'period', 'time', true);

        // añadimos un botón para el modal delete-logs
        $this->addButton($viewName, [
            'action' => 'delete-logs',
            'color' => 'warning',
            'icon' => 'fa-solid fa-trash-alt',
            'label' => 'delete',
            'type' => 'modal',
        ]);
    }

    protected function createViewsWorkEvents(string $viewName = 'ListWorkEvent'): void
    {
        $events = $this->codeModel->all('work_events', 'name', 'name');

        $this->addView($viewName, 'WorkEvent', 'work-events', 'fa-solid fa-calendar-alt')
            ->addOrderBy(['creation_date'], 'creation-date')
            ->addOrderBy(['done_date'], 'date')
            ->addOrderBy(['execution_time'], 'duration')
            ->addOrderBy(['id'], 'id', 2)
            ->addSearchFields(['name', 'value'])
            ->setSettings('btnNew', false)
            ->addFilterSelect('done', 'status', 'done', [
                '' => '------',
                '0' => Tools::trans('pending'),
                '1' => Tools::trans('done'),
            ])
            ->addFilterSelect('name', 'name', 'name', $events)
            ->addFilterPeriod('creation_date', 'period', 'creation_date', true);
    }

    protected function deleteLogsAction(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        } elseif (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return;
        }

        $from = $this->request->input('delete_from', '');
        $to = $this->request->input('delete_to', '');
        $channel = $this->request->input('delete_channel', '');

        $query = LogMessage::table()
            ->whereGte('time', $from)
            ->whereLte('time', $to);

        // si el canal es 'audit' no se pueden borrar los logs
        if ('audit' === $channel) {
            Tools::log()->warning('cant-delete-audit-log');
            return;
        } elseif ($channel !== '') {
            $query->whereEq('channel', $channel);
        } else {
            $query->whereNotEq('channel', 'audit');
        }

        if (false === $query->delete()) {
            Tools::log()->warning('record-deleted-error');
            return;
        }

        Tools::log()->notice('record-deleted-correctly');
    }


    protected function enableCronJobAction(bool $value): void
    {
        if (false === $this->validateFormToken()) {
            return;
        } elseif (false === $this->user->can('EditCronJob', 'update')) {
            Tools::log()->warning('not-allowed-modify');
            return;
        }

        $codes = $this->request->request->getArray('codes');
        if (false === is_array($codes)) {
            return;
        }

        foreach ($codes as $code) {
            $cron = new CronJob();
            if (false === $cron->load($code)) {
                continue;
            }

            $cron->enabled = $value;
            if (false === $cron->save()) {
                Tools::log()->warning('record-save-error');
                return;
            }
        }

        Tools::log()->notice('record-updated-correctly');
    }

    /**
     * @param string $action
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'delete-logs':
                $this->deleteLogsAction();
                break;

            case 'disable-cronjob':
                $this->enableCronJobAction(false);
                break;

            case 'enable-cronjob':
                $this->enableCronJobAction(true);
                break;
        }

        return parent::execPreviousAction($action);
    }
}
