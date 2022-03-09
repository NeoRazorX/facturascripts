<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Base\MiniLog;

trait LogErrorsTrait
{
    protected function logErrors()
    {
        if ($this->getStatus() > 1) {
            foreach (MiniLog::read('', ['critical', 'error', 'warning']) as $item) {
                error_log($item['message']);
            }
        }

        MiniLog::clear();
    }

    protected function searchAuditLog(string $modelclass, string $modelcode): bool
    {
        $found = false;
        foreach (MiniLog::read('audit') as $log) {
            if ($log['context']['model-class'] === $modelclass && $log['context']['model-code'] === $modelcode) {
                $found = true;
                break;
            }
        }
        return $found;
    }
}
