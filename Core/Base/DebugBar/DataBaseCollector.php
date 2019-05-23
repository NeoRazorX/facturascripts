<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019   Carlos García Gómez     <carlos@facturascripts.com>
 * Copyright (C) 2017   Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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
namespace FacturaScripts\Core\Base\DebugBar;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use FacturaScripts\Core\Base\MiniLog;

/**
 * This class traces the SQL queries
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class DataBaseCollector extends DataCollector implements Renderable, AssetProvider
{

    /**
     * App log manager
     *
     * @var MiniLog
     */
    protected $miniLog;

    /**
     * DataBaseCollector constructor.
     *
     * @param MiniLog $miniLog
     */
    public function __construct($miniLog)
    {
        $this->miniLog = $miniLog;
    }

    /**
     * Called by the DebugBar when data needs to be collected
     *
     * @return array Collected data
     */
    public function collect()
    {
        $queries = [];
        $totalExecTime = 0;
        foreach ($this->miniLog->read(['sql']) as $log) {
            $queries[] = [
                'sql' => $log['message'],
                'duration' => 0,
                'duration_str' => 0,
            ];
            $totalExecTime += 0;
        }

        return [
            'nb_statements' => count($queries),
            'accumulated_duration' => $totalExecTime,
            'statements' => $queries,
        ];
    }

    /**
     * Returns the needed assets
     *
     * @return array
     */
    public function getAssets()
    {
        $basePath = '../../../../../../';

        return [
            'css' => $basePath . 'Core/Assets/CSS/phpdebugbar.custom-widget.css',
            'js' => $basePath . 'Core/Assets/JS/phpdebugbar.custom-widget.js',
        ];
    }

    /**
     * Returns the unique name of the collector
     *
     * @return string
     */
    public function getName()
    {
        return 'db';
    }

    /**
     * Returns a hash where keys are control names and their values
     * an array of options as defined in {@see DebugBar\JavascriptRenderer::addControl()}
     *
     * @return array
     */
    public function getWidgets()
    {
        return [
            'database' => [
                'icon' => 'database',
                'tooltip' => 'Database',
                'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
                'map' => 'db',
                'default' => '[]',
            ],
            'database:badge' => [
                'map' => 'db.nb_statements',
                'default' => 0,
            ],
        ];
    }
}
