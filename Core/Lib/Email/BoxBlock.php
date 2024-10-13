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
 * Description of BoxBlock
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class BoxBlock extends BaseBlock
{
    use ExtensionsTrait;

    /** @var array */
    protected $blocks;

    public function __construct(array $blocks, string $css = '', string $style = '')
    {
        $this->css = $css;
        $this->style = $style;
        $this->blocks = $blocks;
    }

    public function render(bool $footer = false): string
    {
        $this->footer = $footer;
        $return = $this->pipe('render');
        if ($return) {
            return $return;
        }

        $html = '';
        foreach ($this->blocks as $block) {
            if ($block instanceof BaseBlock) {
                $html .= $block->render($footer);
            }
        }

        return '<div class="' . (empty($this->css) ? 'block mb-15' : $this->css) . '">' . $html . '</div>';
    }
}
