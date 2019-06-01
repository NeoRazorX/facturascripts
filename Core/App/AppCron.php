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
namespace FacturaScripts\Core\App;

/**
 * App description
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppCron extends App
{

    public function render()
    {
        foreach ($this->miniLog->read() as $log) {
            $this->response->setContent($this->response->getContent() . $log["message"] . "\n");
        }
        parent::render();
    }

    /**
     * Runs cron.
     *
     * @return bool
     */
    public function run()
    {
        $this->response->headers->set('Content-Type', 'text/plain');
        if ($this->dataBase->connected()) {
            $startTime = new \DateTime();
            $this->miniLog->notice($this->i18n->trans('starting-cron'));

            $this->runPlugins();

            $endTime = new \DateTime();
            $executionTime = $startTime->diff($endTime);
            $this->miniLog->notice($this->i18n->trans('finished-cron', ['%timeNeeded%' => $executionTime->format("%H:%I:%S")]));
            return true;
        }

        $this->response->setContent('DB-ERROR');
        return false;
    }

    /**
     * Runs cron from enabled plugins.
     */
    private function runPlugins()
    {
        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            $cronClass = '\\FacturaScripts\\Plugins\\' . $pluginName . '\\Cron';
            if (class_exists($cronClass)) {
                $this->miniLog->notice($this->i18n->trans('running-plugin-cron', ['%pluginName%' => $pluginName]));
                $cron = new $cronClass($pluginName);
                $cron->run();
            }
        }
    }
}
