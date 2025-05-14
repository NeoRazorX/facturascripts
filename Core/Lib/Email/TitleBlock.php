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

namespace FacturaScripts\Core\Lib\Email;

use FacturaScripts\Core\Template\ExtensionsTrait;

/**
 * Description of TitleBlock
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class TitleBlock extends BaseBlock
{
    use ExtensionsTrait;

    /** @var string */
    protected $text;

    /** @var string */
    protected $type;

    public function __construct(string $text, string $type = 'h2', string $css = '', string $style = '')
    {
        $this->css = $css;
        $this->style = $style;
        $this->text = $text;
        $this->type = $type;
    }

    public function render(bool $footer = false): string
    {
        $this->footer = $footer;
        $return = $this->pipe('render');
        return $return ?? '<' . $this->type . ' class="' . (empty($this->css) ? 'title' : $this->css) . '">'
        . $this->text . '</' . $this->type . '>';
    }
}
