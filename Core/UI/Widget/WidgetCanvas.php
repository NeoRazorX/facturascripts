<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI\Widget;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Template\UI\Widget;
use FacturaScripts\Core\Tools;

class WidgetCanvas extends Widget
{
    public function __construct(string $name, ?string $field = null, ?string $label = null)
    {
        parent::__construct($name, $field, $label);

        AssetManager::add('js', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js');
    }

    public function render(string $context = ''): string
    {
        return '<div class="form-group">' . "\n"
            . '<label for="' . $this->id() . '">' . $this->label . '</label>' . "\n"
            . '<button type="button" class="btn btn-sm btn-light" onclick="signaturePad.clear();">'
            . '<i class="fa fa-eraser mr-1"></i> ' . Tools::lang()->trans('clear') . '</button>' . "\n"
            . '<canvas id="' . $this->id() . '" class="border" height="100"></canvas>' . "\n"
            . '</div>' . "\n"
            . '<script>' . "\n"
            . 'let canvas = document.getElementById("' . $this->id() . '");' . "\n"
            . 'canvas.width = window.innerWidth - 40;' . "\n"
            . 'let signaturePad = new SignaturePad(canvas);' . "\n"
            . '</script>';
    }
}
