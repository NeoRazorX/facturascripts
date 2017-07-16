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

namespace FacturaScripts\Core\Base\DataBase;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Clase para tracear a MySQL.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @source https://github.com/maximebf/php-debugbar/issues/326
 * @source https://github.com/snowair/phalcon-debugbar/blob/master/src/Phalcon/Db/Profiler.php
 * @source https://github.com/WordPress/WordPress/blob/4.8-branch/wp-includes/wp-db.php
 * @source https://github.com/maximebf/php-debugbar/issues/213
 */
class TraceableMysql extends DataCollector implements Renderable, AssetProvider
{
    /**
     * @var
     */
    protected $queries;

    /**
     * TraceableMysql constructor.
     *
     * @param $queries
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
            $query = explode('----', $q);
            $queries[] = [
                'sql' => $query[0],
                'duration' => $query[1],
                'duration_str' => $this->formatDuration($query[1])
            ];
            $totalExecTime += $query[1];
        }
        return [
            'nb_statements' => count($queries),
            'accumulated_duration' => $totalExecTime,
            'accumulated_duration_str' => $this->formatDuration($totalExecTime),
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
        return 'mysql_queries';
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
                'icon' => 'arrow-right',
                'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
                'map' => 'queries',
                'default' => '[]'
            ],
            'database:badge' => [
                'map' => 'queries.nb_statements',
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
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/sqlqueries/widget.js'
        ];
    }

    /**
     * TODO
     *
     * @param $statement
     *
     * @return mixed
     */
    public function query($statement)
    {
        return $this->profileCall('query', $statement, func_get_args());
    }

    /**
     * TODO
     *
     * @param $stmt
     */
    public function addExecutedStatement($stmt)
    {
        $this->queries = $stmt;
    }

    /**
     * TODO
     *
     * @param $method
     * @param $sql
     * @param array $args
     *
     * @return mixed
     */
    protected function profileCall($method, $sql, array $args)
    {

        $result = call_user_func_array([$this->queries, $method], $args);
        print_r($result);

        return $result;
    }
}
