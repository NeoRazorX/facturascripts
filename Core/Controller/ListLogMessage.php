<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
        $this->addView($viewName, 'CronJob', 'crons', 'fa-solid fa-cogs')
            ->addSearchFields(['jobname', 'pluginname'])
            ->addOrderBy(['jobname'], 'job-name')
            ->addOrderBy(['pluginname'], 'plugin')
            ->addOrderBy(['date'], 'date')
            ->addOrderBy(['duration'], 'duration');

        // filtros
        $this->addFilterPeriod($viewName, 'date', 'period', 'date', true);

        $plugins = $this->codeModel->all('cronjobs', 'pluginname', 'pluginname');
        $this->addFilterSelect($viewName, 'pluginname', 'plugin', 'pluginname', $plugins);

        $this->addFilterSelect($viewName, 'enabled', 'status', 'enabled', [
            '' => '------',
            '0' => Tools::lang()->trans('disabled'),
            '1' => Tools::lang()->trans('enabled'),
        ]);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);

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
            'icon' => 'far fa-square',
            'label' => 'disable'
        ]);
    }

    protected function createViewsLogs(string $viewName = 'ListLogMessage'): void
    {
        $this->addView($viewName, 'LogMessage', 'history', 'fa-solid fa-history')
            ->addSearchFields(['context', 'message', 'uri'])
            ->addOrderBy(['time', 'id'], 'date', 2)
            ->addOrderBy(['level'], 'level')
            ->addOrderBy(['ip'], 'ip');

        // filtros
        $channels = $this->codeModel->all('logs', 'channel', 'channel');
        $this->addFilterSelect($viewName, 'channel', 'channel', 'channel', $channels);

        $levels = $this->codeModel->all('logs', 'level', 'level');
        $this->addFilterSelect($viewName, 'level', 'level', 'level', $levels);

        $this->addFilterAutocomplete($viewName, 'nick', 'user', 'nick', 'users');
        $this->addFilterAutocomplete($viewName, 'ip', 'ip', 'ip', 'logs');

        $uris = $this->codeModel->all('logs', 'uri', 'uri');
        $this->addFilterSelect($viewName, 'url', 'url', 'uri', $uris);

        $models = $this->codeModel->all('logs', 'model', 'model');
        $this->addFilterSelect($viewName, 'model', 'doc-type', 'model', $models);

        $this->addFilterPeriod($viewName, 'time', 'period', 'time', true);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);

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
        $this->addView($viewName, 'WorkEvent', 'work-events', 'fa-solid fa-calendar-alt')
            ->addOrderBy(['creation_date'], 'creation-date')
            ->addOrderBy(['done_date'], 'date')
            ->addOrderBy(['id'], 'id', 2)
            ->addSearchFields(['name', 'value'])
            ->setSettings('btnNew', false);

        // filtros
        $this->addFilterSelect($viewName, 'done', 'status', 'done', [
            '' => '------',
            '0' => Tools::lang()->trans('pending'),
            '1' => Tools::lang()->trans('done'),
        ]);

        $events = $this->codeModel->all('work_events', 'name', 'name');
        $this->addFilterSelect($viewName, 'name', 'name', 'name', $events);

        $this->addFilterPeriod($viewName, 'creation_date', 'period', 'creation_date', true);
    }

    protected function deleteLogsAction(): void
    {
        if (false === $this->validateFormToken()) {
            return;
        } elseif (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return;
        }

        $from = $this->request->request->get('delete_from', '');
        $to = $this->request->request->get('delete_to', '');
        $channel = $this->request->request->get('delete_channel', '');

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
            if (false === $cron->loadFromCode($code)) {
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
