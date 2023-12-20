<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\WorkQueue;

class Cron implements ControllerInterface
{
    public function __construct(string $className, string $url = '')
    {
    }

    public function getPageData(): array
    {
        return [];
    }

    public function run(): void
    {
        header('Content-Type: text/plain');

        echo <<<END

  ______         _                    _____           _       _       
 |  ____|       | |                  / ____|         (_)     | |      
 | |__ __ _  ___| |_ _   _ _ __ __ _| (___   ___ _ __ _ _ __ | |_ ___ 
 |  __/ _` |/ __| __| | | | '__/ _` |\___ \ / __| '__| | '_ \| __/ __|
 | | | (_| | (__| |_| |_| | | | (_| |____) | (__| |  | | |_) | |_\__ \
 |_|  \__,_|\___|\__|\__,_|_|  \__,_|_____/ \___|_|  |_| .__/ \__|___/
                                                       | |            
                                                       |_|
END;

        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('starting-cron');
        Tools::log('cron')->notice('starting-cron');

        ob_flush();

        // ejecutamos el cron de cada plugin
        $this->runPlugins();

        // si se está ejecutando en modo cli, ejecutamos la cola de trabajos
        while (PHP_SAPI === 'cli') {
            if (false === WorkQueue::run()) {
                break;
            }
        }

        $executionTime = Kernel::getExecutionTime();
        echo PHP_EOL . PHP_EOL . Tools::lang()->trans('finished-cron', ['%timeNeeded%' => $executionTime]) . PHP_EOL;
        Tools::log()->notice('finished-cron', ['%timeNeeded%' => $executionTime]);
    }

    protected function runPlugins(): void
    {
        foreach (Plugins::enabled() as $pluginName) {
            $cronClass = '\\FacturaScripts\\Plugins\\' . $pluginName . '\\Cron';
            if (false === class_exists($cronClass)) {
                continue;
            }

            echo PHP_EOL . Tools::lang()->trans('running-plugin-cron', ['%pluginName%' => $pluginName]) . ' ... ';
            Tools::log('cron')->notice('running-plugin-cron', ['%pluginName%' => $pluginName]);

            try {
                $cron = new $cronClass($pluginName);
                $cron->run();
            } catch (Exception $ex) {
                echo $ex->getMessage() . PHP_EOL;
                Tools::log()->error($ex->getMessage());
            }

            ob_flush();

            // si no se está ejecutando en modo cli y lleva más de 20 segundos, se detiene
            if (PHP_SAPI != 'cli' && Kernel::getExecutionTime() > 20) {
                break;
            }
        }
    }
}
