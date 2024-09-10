<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\WorkQueue;

abstract class WorkerClass
{
    abstract public function run(WorkEvent $event): bool;

    protected function done(): bool
    {
        $this->preventNewEvents([]);

        return true;
    }

    protected function stopPropagation(): bool
    {
        $this->preventNewEvents([]);

        return false;
    }

    protected function preventNewEvents(array $eventNames): void
    {
        WorkQueue::preventNewEvents($eventNames);
    }
}
