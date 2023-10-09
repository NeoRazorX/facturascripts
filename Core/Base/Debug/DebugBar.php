<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\Debug;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Translator;
use FacturaScripts\Dinamic\Lib\AssetManager;

/**
 * Description of DebugBar
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class DebugBar extends DumbBar
{

    /**
     * @var array
     */
    private static $end = [];

    /**
     * @var array
     */
    private static $init = [];

    public static function end(string $task = '')
    {
        self::$end[$task] = microtime(true);
    }

    public function render(): string
    {
        $items = [];
        $this->addItemTimer($items);
        $this->addItemMemory($items);
        $this->addItemAssets($items);
        $this->addItemInputs($items);
        $this->addItemLogs($items);
        $this->addItemTranslations($items);

        return '<div class="debugbar"><ul>' . $this->renderItems($items) . '</ul>' . $this->renderSections($items) . '</div>';
    }

    public function renderHead(): string
    {
        return '<link rel="stylesheet" href="' . FS_ROUTE . '/Dinamic/Assets/CSS/debugbar.css" />'
            . '<script src="' . FS_ROUTE . '/Dinamic/Assets/JS/DebugBar.js"></script>';
    }

    public static function start(string $task = '')
    {
        self::$init[$task] = microtime(true);
    }

    private function addItem(array &$items, string $label, array $data, bool $counter = false)
    {
        $key = 1 + count($items);
        $items[$key] = ['label' => $label, 'data' => $data, 'counter' => $counter];
    }

    private function addItemAssets(array &$items)
    {
        foreach (['css', 'js'] as $type) {
            $label = '<i class="fas fa-file"></i> ' . strtoupper($type);
            $data = AssetManager::get($type);
            if (!empty($data)) {
                $this->addItem($items, $label, $data, true);
            }
        }
    }

    private function addItemInputs(array &$items)
    {
        $inputs = [
            'get' => filter_input_array(INPUT_GET),
            'post' => filter_input_array(INPUT_POST),
            'cookie' => filter_input_array(INPUT_COOKIE)
        ];

        foreach ($inputs as $type => $rows) {
            if (empty($rows)) {
                continue;
            }

            $label = '<i class="fas fa-keyboard"></i> ' . $type;
            $data = [];
            foreach ($rows as $key => $value) {
                if (is_array($value)) {
                    $data[] = [$key, json_encode($value)];
                    continue;
                }

                $data[] = [$key, $value];
            }

            $this->addItem($items, $label, $data, true);
        }
    }

    private function addItemLogs(array &$items)
    {
        $channels = [];

        $lastMicrotime = self::$init[''];
        foreach (MiniLog::read() as $log) {
            if (!isset($channels[$log['channel']])) {
                $channels[$log['channel']] = [
                    'label' => $log['channel'],
                    'data' => []
                ];
            }

            $diff = ($log['time'] - $lastMicrotime) * 1000;
            $diffText = round($diff) > 0 ? '&#8593;+' . number_format($diff) . 'ms' : '&#8593;';
            $channels[$log['channel']]['data'][] = [
                'level' => $log['level'], 'message' => $log['message'], 'time' => $diffText
            ];
            $lastMicrotime = $log['time'];
        }

        foreach ($channels as $channel) {
            $label = '<i class="fas fa-file-medical-alt"></i> ' . $channel['label'];
            $this->addItem($items, $label, $channel['data'], true);
        }
    }

    private function addItemMemory(array &$items)
    {
        $usage = memory_get_usage();
        $peak = memory_get_peak_usage();

        $label = '<i class="fas fa-memory"></i> ' . $this->getSize(max([$usage, $peak]));
        $data = [
            ['Memory usage', $this->getSize($usage)],
            ['Memory peak', $this->getSize($peak)]
        ];

        $this->addItem($items, $label, $data);
    }

    private function addItemTimer(array &$items)
    {
        $totalTime = microtime(true) - self::$init[''];
        $label = '<i class="fas fa-hourglass-half"></i> ' . number_format($totalTime * 1000) . 'ms';

        $data = [];
        foreach (self::$init as $task => $init) {
            $end = self::$end[$task] ?? microtime(true);
            $diff = $end - $init;
            $data[] = [
                'task' => empty($task) ? 'Total' : $task,
                'time' => number_format($diff * 1000) . 'ms'
            ];
        }

        $this->addItem($items, $label, $data);
    }

    private function addItemTranslations(array &$items)
    {
        $i18n = new Translator();
        $missing = $i18n->getMissingStrings();
        if (count($missing) > 0) {
            $label = '<i class="fas fa-language"></i> Missing';
            $this->addItem($items, $label, $missing, true);
        }
    }

    private function getSize(int $size): string
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $unit[$i];
    }

    private function noHtml(string $string): string
    {
        return str_replace(
            ['<', '>', '"', "'"], ['&lt;', '&gt;', '&quot;', '&#39;'], $string
        );
    }

    private function renderItems(array $items): string
    {
        $html = '<li class="debugbar-item debugbar-minimize">'
            . '<a href="#" onclick="return hideAllDebugBar();"><i class="fas fa-chevron-down"></i></a>'
            . '</li>';

        foreach ($items as $key => $item) {
            $label = $item['counter'] ? $item['label'] . ' <span>' . count($item['data']) . '</span>' : $item['label'];
            $html .= '<li class="debugbar-item">'
                . '<a href="#debugSection' . $key . '" id="debugbarBtn' . $key . '" onclick="return showDebugBarSection(' . $key . ')">'
                . $label
                . '</a>'
                . '</li>';
        }

        return $html;
    }

    private function renderSections(array $items): string
    {
        $html = '';
        foreach ($items as $key => $item) {
            $html .= '<div id="debugbarSection' . $key . '" class="debugbar-section">'
                . '<table class="debugbar-section-table">' . $this->renderTable($item['data']) . '</table>'
                . '</div>';
        }

        return $html;
    }

    private function renderTable(array $data): string
    {
        $html = '';
        $count = 0;
        foreach ($data as $row) {
            $count++;
            if (false === is_array($row)) {
                $html .= '<tr><td>' . $this->noHtml($row) . '</td></tr>';
                continue;
            }

            $html .= '<tr><td>#' . $count . '</td>';
            foreach ($row as $cell) {
                $html .= is_array($cell) ? '<td>' . var_export($cell, true) . '</td>' : '<td>' . $this->noHtml($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        return $html;
    }
}
