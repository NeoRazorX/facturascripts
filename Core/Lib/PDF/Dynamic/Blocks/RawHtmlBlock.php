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

/**
 * Raw HTML block with basic sanitization: removes scripts, on* attributes and javascript: urls.
 */
class RawHtmlBlock extends AbstractBlock
{
    /** @var string */
    protected $html;

    public function __construct(string $html)
    {
        $this->html = $html;
    }

    public function render(): string
    {
        return $this->sanitize($this->html);
    }

    protected function sanitize(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script\s*>/is', '', $html);
        $html = preg_replace('/<script\b[^>]*\/?>/i', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        return preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*/i', '$1=$2#', $html);
    }
}
