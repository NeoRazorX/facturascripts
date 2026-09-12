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
 * General document header, identical to the one in the core PDF export
 * (PDFDocument::insertHeader): logo on one side and the company block on the
 * other (name in big font, fiscal number + address, contact data).
 */
class DocumentHeaderBlock extends AbstractBlock
{
    /** @var Empresa */
    protected $empresa;

    /** @var bool */
    protected $logoLeft;

    /** @var ?string */
    protected $logoSrc;

    public function __construct(Empresa $empresa, ?string $logoSrc = null, bool $logoLeft = true)
    {
        $this->empresa = $empresa;
        $this->logoSrc = $logoSrc;
        $this->logoLeft = $logoLeft;
    }

    public function render(): string
    {
        $logo = '<div class="header-logo"><img src="' . $this->escape($this->resolveLogo()) . '" alt=""/></div>';

        $info = '<div class="company-info text-' . ($this->logoLeft ? 'right' : 'left') . '">'
            . '<div class="company-name">' . $this->escape($this->empresa->nombre) . '</div>'
            . '<div>' . $this->escape($this->fiscalLine()) . '</div>'
            . '<div class="mt-2">' . $this->escape($this->contactLine()) . '</div>'
            . '</div>';

        $parts = $this->logoLeft ? $logo . $info : $info . $logo;
        return '<div class="' . $this->css('document-header') . '">' . $parts . '</div>';
    }

    protected function contactLine(): string
    {
        $parts = [];
        foreach (['telefono1', 'telefono2', 'email', 'web'] as $field) {
            if (!empty($this->empresa->{$field})) {
                $parts[] = $this->empresa->{$field};
            }
        }

        return implode(' · ', $parts);
    }

    protected function fiscalLine(): string
    {
        $address = $this->empresa->direccion ?? '';
        if (!empty($this->empresa->codpostal)) {
            $address .= empty($address) ? $this->empresa->codpostal : ', ' . $this->empresa->codpostal;
        }
        if (!empty($this->empresa->ciudad)) {
            $address .= empty($address) ? $this->empresa->ciudad : ', ' . $this->empresa->ciudad;
        }
        if (!empty($this->empresa->provincia)) {
            $address .= ' (' . $this->empresa->provincia . ')';
        }

        return empty($address) ?
            (string)$this->empresa->cifnif :
            $this->empresa->cifnif . ' - ' . $address;
    }

    protected function imageToBase64(string $path): string
    {
        if (false === file_exists($path) || false === is_file($path)) {
            return '';
        }

        return 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
    }

    protected function resolveLogo(): string
    {
        if (!empty($this->logoSrc)) {
            return str_starts_with($this->logoSrc, 'data:') ?
                $this->logoSrc :
                $this->imageToBase64($this->logoSrc);
        }

        if (!empty($this->empresa->idlogo)) {
            $logoFile = new AttachedFile();
            if ($logoFile->load($this->empresa->idlogo) && $logoFile->isImage()) {
                return $this->imageToBase64($logoFile->getFullPath());
            }
        }

        // same fallback as the core PDF export
        return $this->imageToBase64(FS_FOLDER . '/Dinamic/Assets/Images/horizontal-logo.png');
    }
}
