<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Traits;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Tools;

trait LogErrorsTrait
{
    protected function logErrors(bool $force = false): void
    {
        if ($this->getStatus() > 1 || $force) {
            foreach (MiniLog::read('', ['critical', 'error', 'warning']) as $item) {
                error_log($item['message']);
                if (!empty($item['context'])) {
                    error_log(print_r($item['context'], true));
                }
            }

            // guardamos la lista de consultas sql en un archivo
            $queries = [];
            foreach (MiniLog::read('database') as $item) {
                $queries[] = $item['message'];
            }
            $file_path = Tools::folder('MyFiles', 'test_error_' . date('Y-m-d_H-i-s_') . rand(0, 1000) . '.log');
            file_put_contents($file_path, implode(PHP_EOL, $queries) . PHP_EOL, FILE_APPEND);
            error_log('Database queries in ' . $file_path . PHP_EOL);

            // mostramos las 5 últimas
            foreach (array_slice($queries, -5) as $query) {
                error_log($query);
            }
        }

        MiniLog::clear();
    }

    protected function searchAuditLog(string $modelClass, string $modelCode): bool
    {
        foreach (MiniLog::read('audit') as $log) {
            if ($log['context']['model-class'] === $modelClass && $log['context']['model-code'] === $modelCode) {
                return true;
            }
        }

        return false;
    }
}
