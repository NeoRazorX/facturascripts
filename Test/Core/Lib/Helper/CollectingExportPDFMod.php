<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib\Helper;

use FacturaScripts\Core\Contract\ExportPDFInterface;
use FacturaScripts\Core\Lib\ExportPDF\BaseMod;

final class CollectingExportPDFMod extends BaseMod
{
    /** @var array<int, string> */
    private array $calls;

    /**
     * @param array<int, string> $calls
     */
    public function __construct(array &$calls)
    {
        $this->calls =& $calls;
    }

    public function customizeFormat(ExportPDFInterface $export, array &$context): bool
    {
        $context['options']['lang'] = 'en_GB';
        return true;
    }

    public function beforeAddModel(ExportPDFInterface $export, array &$context): bool
    {
        $this->calls[] = 'before';
        $export->setOrientation('landscape');
        $export->setSize('a5');
        return true;
    }

    public function afterAddModel(ExportPDFInterface $export, array &$context): bool
    {
        $this->calls[] = 'after';
        return true;
    }

    public function appendExtraPages(ExportPDFInterface $export, array &$context): void
    {
        $this->calls[] = 'extra';
    }
}
