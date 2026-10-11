<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\PDF\Dynamic\Blocks;

use FacturaScripts\Core\Lib\PDF\Dynamic\BlockInterface;

/**
 * Base class for all dynamic PDF blocks.
 */
abstract class AbstractBlock implements BlockInterface
{
    /** @var string */
    protected $cssClass = '';

    public function setCssClass(string $cssClass): self
    {
        $this->cssClass = $cssClass;
        return $this;
    }

    protected function css(string $base): string
    {
        return trim($base . ' ' . $this->cssClass);
    }

    protected function escape(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}
