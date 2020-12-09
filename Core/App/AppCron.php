<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
final class AppCron extends App
{

    /**
     * Returns the data into the standard output.
     */
    public function render()
    {
        $this->response->headers->set('Content-Type', 'text/plain');

        $content = $this->response->getContent();
        foreach ($this->toolBox()->log()->readAll() as $log) {
            $content .= empty($content) ? $log["message"] : "\n" . $log["message"];
        }

        $this->response->setContent($content . "\n");
        parent::render();
    }

    /**
     * Runs cron.
     *
     * @return bool
     */
    public function run(): bool
    {
        if (!parent::run()) {
            return false;
        }

        $startTime = new \DateTime();
        $this->toolBox()->i18nLog()->notice('starting-cron');

        $this->runPlugins();

        $endTime = new \DateTime();
        $executionTime = $startTime->diff($endTime);
        $this->toolBox()->i18nLog()->notice('finished-cron', ['%timeNeeded%' => $executionTime->format("%H:%I:%S")]);
        return true;
    }

    /**
     * 
     * @param int    $status
     * @param string $message
     */
    protected function die(int $status, string $message = '')
    {
        $content = $this->toolBox()->i18n()->trans($message);
        $this->response->setContent($content);
        $this->response->setStatusCode($status);
    }

    /**
     * Runs cron from enabled plugins.
     */
    private function runPlugins()
    {
        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            $cronClass = '\\FacturaScripts\\Plugins\\' . $pluginName . '\\Cron';
            if (class_exists($cronClass)) {
                $this->toolBox()->i18nLog()->notice('running-plugin-cron', ['%pluginName%' => $pluginName]);
                $cron = new $cronClass($pluginName);
                $cron->run();
            }
        }
    }
}
