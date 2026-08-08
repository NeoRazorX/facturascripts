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

use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Dinamic\Model\AttachedFile;

/**
 * Company header: logo (from the company attached file, embedded as base64)
 * plus name, address and fiscal data. The logo can go left, right or centered.
 */
class CompanyHeaderBlock extends AbstractBlock
{
    /** @var Empresa */
    protected $empresa;

    /** @var string */
    protected $logoAlign;

    public function __construct(Empresa $empresa, string $logoAlign = 'left')
    {
        $this->empresa = $empresa;
        $this->logoAlign = in_array($logoAlign, ['center', 'right']) ? $logoAlign : 'left';
    }

    public function render(): string
    {
        $logo = $this->renderLogo();
        $data = '<div class="company-data">'
            . '<div class="title">' . $this->escape($this->empresa->nombre) . '</div>'
            . '<div>' . $this->escape($this->empresa->cifnif) . '</div>'
            . '<div>' . $this->escape($this->combineAddress()) . '</div>'
            . '<div>' . $this->escape($this->combineContact()) . '</div>'
            . '</div>';

        if (empty($logo)) {
            return '<div class="' . $this->css('company-header') . '">' . $data . '</div>';
        }

        if ($this->logoAlign === 'center') {
            return '<div class="' . $this->css('company-header') . '" style="flex-direction: column; align-items: center; text-align: center;">'
                . $logo . $data . '</div>';
        }

        $parts = $this->logoAlign === 'right' ? $data . $logo : $logo . $data;
        return '<div class="' . $this->css('company-header') . '">' . $parts . '</div>';
    }

    protected function combineAddress(): string
    {
        $parts = [];
        foreach ([$this->empresa->direccion, $this->empresa->codpostal, $this->empresa->ciudad, $this->empresa->provincia] as $part) {
            if (!empty($part)) {
                $parts[] = $part;
            }
        }

        return implode(', ', $parts);
    }

    protected function combineContact(): string
    {
        $parts = [];
        foreach ([$this->empresa->telefono1, $this->empresa->telefono2, $this->empresa->email, $this->empresa->web] as $part) {
            if (!empty($part)) {
                $parts[] = $part;
            }
        }

        return implode(' · ', $parts);
    }

    protected function renderLogo(): string
    {
        if (empty($this->empresa->idlogo)) {
            return '';
        }

        $logoFile = new AttachedFile();
        if (false === $logoFile->load($this->empresa->idlogo) || false === $logoFile->isImage()) {
            return '';
        }

        $path = $logoFile->getFullPath();
        if (false === file_exists($path)) {
            return '';
        }

        $src = 'data:' . $logoFile->mimetype . ';base64,' . base64_encode(file_get_contents($path));
        return '<div><img src="' . $src . '" alt=""/></div>';
    }
}
