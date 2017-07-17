<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra  francesc.pineda.segarra@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base\DataBase\DataCollector;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Clase para tracear a PostgreSQL.
 * Por ahora básicamente lee las SQL del miniLog, lo ideal sería utilizarlo como con PDO.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 *
 * Info relaticionada, WP ya utiliza esto con mysqli, así que nos sirve de ejemplo:
 * @source https://github.com/maximebf/php-debugbar/issues/326
 * @source https://github.com/snowair/phalcon-debugbar/blob/master/src/Phalcon/Db/Profiler.php
 * @source https://github.com/WordPress/WordPress/blob/4.8-branch/wp-includes/wp-db.php
 * @source https://github.com/maximebf/php-debugbar/issues/213
 */
class PostgresqlCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * @var
     */
    protected $queries;

    /**
     * PostgresqlCollector constructor.
     *
     * @param array $queries
     */
    public function __construct($queries)
    {
        $this->queries = $queries;
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
        foreach ($this->queries as $q) {
            $queries[] = [
                'sql' => $q['message'],
                'duration' => 0,
                'duration_str' => 0
            ];
            $totalExecTime += 0;
        }
        return [
            'nb_statements' => count($queries),
            //'nb_failed_statements' => 0,
            'accumulated_duration' => $totalExecTime,
            //'accumulated_duration_str' => 0,
            //'memory_usage' => 0,
            //'peak_memory_usage' => 0,
            'statements' => $queries
        ];
    }

    /**
     * Returns the unique name of the collector
     *
     * @return string
     */
    public function getName()
    {
        return 'pgsql';
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
                'icon' => 'phpdebugbar-fa-database',
                "tooltip" => "Using PostgreSQL",
                'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
                'map' => 'pgsql',
                'default' => '[]'
            ],
            'database:badge' => [
                'map' => 'pgsql.nb_statements',
                'default' => 0
            ]
        ];
    }

    /**
     * TODO
     *
     * @return array
     */
    public function getAssets()
    {
        return [
            'css' => 'assets/css/phpdebugbar.custom-widget.css',
            'js' => 'assets/js/phpdebugbar.custom-widget.js'
        ];
    }
}
