<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of ButtonBlock
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ButtonBlock extends BaseBlock
{

    /**
     *
     * @var string
     */
    protected $label;

    /**
     *
     * @var string
     */
    protected $link;

    /**
     * 
     * @param string $label
     * @param string $link
     */
    public function __construct(string $label, string $link)
    {
        $this->label = $label;
        $this->link = $link;
    }

    /**
     * 
     * @return string
     */
    public function render(): string
    {
        return '<a class="btn" href="' . $this->link . '">' . $this->label . '</a>';
    }
}
