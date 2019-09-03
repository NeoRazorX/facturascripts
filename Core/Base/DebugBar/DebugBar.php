<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;

/**
 * Description of DebugBar
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class DebugBar extends DumbBar
{

    /**
     *
     * @var array
     */
    private static $end = [];

    /**
     *
     * @var array
     */
    private static $init = [];

    /**
     * 
     * @param string $task
     */
    public static function end($task = '')
    {
        self::$end[$task] = microtime(true);
    }

    /**
     * 
     * @return string
     */
    public function render(): string
    {
        $items = [];
        $this->addItemTimer($items);
        $this->addItemMemory($items);
        $this->addItemLogs($items);
        $this->addItemTranslations($items);

        return '<nav class="navbar navbar-dark bg-secondary fixed-bottom d-print-none">'
            . '<ul class="navbar-nav flex-row mr-auto">' . $this->renderItems($items) . '</ul>'
            . '</nav>'
            . $this->renderModals($items);
    }

    /**
     * 
     * @param string $task
     */
    public static function start($task = '')
    {
        self::$init[$task] = microtime(true);
    }

    /**
     * 
     * @param array  $items
     * @param string $label
     * @param array  $data
     * @param bool   $big
     */
    private function addItem(array &$items, string $label, array $data, bool $big = false)
    {
        $key = 1 + count($items);
        $items[$key] = ['label' => $label, 'data' => $data, 'big' => $big];
    }

    /**
     * 
     * @param array $items
     */
    private function addItemLogs(array &$items)
    {
        $channels = [];

        $logger = new MiniLog();
        foreach ($logger->readAll(MiniLog::ALL_LEVELS) as $log) {
            if (!isset($channels[$log['channel']])) {
                $channels[$log['channel']] = [
                    'label' => $log['channel'],
                    'data' => [
                        ['level' => $log['level'], 'message' => $log['message']]
                    ]
                ];
                continue;
            }

            $channels[$log['channel']]['data'][] = ['level' => $log['level'], 'message' => $log['message']];
        }

        foreach ($channels as $channel) {
            $label = '<i class="fas fa-file-medical-alt"></i> ' . $channel['label'];
            $this->addItem($items, $label, $channel['data'], true);
        }
    }

    /**
     * 
     * @param array $items
     */
    private function addItemMemory(array &$items)
    {
        $usage = $this->getSize(memory_get_usage());
        $peak = $this->getSize(memory_get_peak_usage());

        $label = '<i class="fas fa-memory"></i> ' . $usage;
        $data = [
            ['Memory usage', $usage],
            ['Memory peak', $peak]
        ];

        $this->addItem($items, $label, $data);
    }

    /**
     * 
     * @param array $items
     */
    private function addItemTimer(array &$items)
    {
        $totalTime = microtime(true) - self::$init[''];
        $label = '<i class="fas fa-hourglass-half"></i> ' . number_format($totalTime * 1000) . 'ms';

        $data = [];
        foreach (self::$init as $task => $init) {
            $end = isset(self::$end[$task]) ? self::$end[$task] : microtime(true);
            $diff = $end - $init;
            $data[] = [
                'task' => empty($task) ? 'Total' : $task,
                'time' => number_format($diff * 1000) . 'ms'
            ];
        }

        $this->addItem($items, $label, $data);
    }

    /**
     * 
     * @param array $items
     */
    private function addItemTranslations(array &$items)
    {
        $i18n = new Translator();
        $missing = $i18n->getMissingStrings();
        if (count($missing) > 0) {
            $label = '<i class="fas fa-language"></i> Missing';
            $this->addItem($items, $label, $missing, true);
        }
    }

    /**
     * 
     * @param int $size
     *
     * @return string
     */
    private function getSize($size)
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
    }

    /**
     * 
     * @param array $items
     *
     * @return string
     */
    private function renderItems(array $items): string
    {
        $html = '';
        foreach ($items as $key => $item) {
            $label = $item['big'] ? $item['label'] . ' <span class="badge badge-light">' . count($item['data']) . '</span>' : $item['label'];
            $html .= '<li class="nav-item mr-3">'
                . '<a href="#debugModal" class="nav-link" data-toggle="modal" data-target="#debugModal' . $key . '">'
                . $label
                . '</a>'
                . '</li>';
        }

        return $html;
    }

    /**
     * 
     * @param array $items
     *
     * @return string
     */
    private function renderModals(array $items): string
    {
        $html = '';
        foreach ($items as $key => $item) {
            $modalDialog = $item['big'] ? 'modal-dialog modal-xl' : 'modal-dialog';
            $html .= '<div class="modal fade" id="debugModal' . $key . '" role="dialog" aria-hidden="true">'
                . '<div class="' . $modalDialog . '" role="document">'
                . '<div class="modal-content">'
                . '<div class="modal-header">'
                . '<h5 class="modal-title">' . $item['label'] . '</h5>'
                . '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                . '</div><div class="table-responsive">'
                . '<table class="table table-hover">' . $this->renderTable($item['data']) . '</table>'
                . '</div></div></div></div>';
        }

        return $html;
    }

    /**
     * 
     * @param array $data
     *
     * @return string
     */
    private function renderTable(array $data): string
    {
        $html = '';
        $count = 0;
        foreach ($data as $row) {
            $count++;
            if (!is_array($row)) {
                $html .= '<tr><td>' . $row . '</td></tr>';
                continue;
            }

            $html .= '<tr><td>#' . $count . '</td>';
            foreach ($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }

        return $html;
    }
}
