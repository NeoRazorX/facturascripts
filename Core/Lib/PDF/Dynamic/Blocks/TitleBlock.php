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

class TitleBlock extends AbstractBlock
{
    /** @var string */
    protected $text;

    /** @var int */
    protected $level;

    /** @var string */
    protected $align;

    public function __construct(string $text, int $level = 1, string $align = 'left')
    {
        $this->text = $text;
        $this->level = min(max($level, 1), 3);
        $this->align = $align;
    }

    public function render(): string
    {
        $tag = 'h' . $this->level;
        return '<' . $tag . ' class="' . $this->css('title text-' . $this->align) . '">'
            . $this->escape($this->text)
            . '</' . $tag . '>';
    }
}
