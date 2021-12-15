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

namespace FacturaScripts\Core\App;

use DateTime;
use FacturaScripts\Core\Base\ToolBox;

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

        $title = <<<END
  ______         _                    _____           _       _       
 |  ____|       | |                  / ____|         (_)     | |      
 | |__ __ _  ___| |_ _   _ _ __ __ _| (___   ___ _ __ _ _ __ | |_ ___ 
 |  __/ _` |/ __| __| | | | '__/ _` |\___ \ / __| '__| | '_ \| __/ __|
 | | | (_| | (__| |_| |_| | | | (_| |____) | (__| |  | | |_) | |_\__ \
 |_|  \__,_|\___|\__|\__,_|_|  \__,_|_____/ \___|_|  |_| .__/ \__|___/
                                                       | |            
                                                       |_|                                   
END;

        $content = $this->response->getContent() . $title;
        foreach (ToolBox::log()::read('', ['critical', 'error', 'info', 'notice', 'warning']) as $log) {
            if ($log['channel'] != 'audit') {
                $content .= "\n" . $log["message"];
            }
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
        if (false === parent::run()) {
            return false;
        }

        $startTime = new DateTime();
        ToolBox::i18nLog()->notice('starting-cron');

        $this->runPlugins();

        $endTime = new DateTime();
        $executionTime = $startTime->diff($endTime);
        ToolBox::i18nLog()->notice('finished-cron', ['%timeNeeded%' => $executionTime->format("%H:%I:%S")]);
        return true;
    }

    /**
     * @param int $status
     * @param string $message
     */
    protected function die(int $status, string $message = '')
    {
        $content = ToolBox::i18n()->trans($message);
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
                ToolBox::i18nLog()->notice('running-plugin-cron', ['%pluginName%' => $pluginName]);
                $cron = new $cronClass($pluginName);
                $cron->run();
            }
        }
    }
}
