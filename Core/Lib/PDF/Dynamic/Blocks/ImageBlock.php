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
 * Image block. Local file paths are embedded as base64 data URIs so that
 * html2canvas can always render them (no CORS or relative path issues).
 */
class ImageBlock extends AbstractBlock
{
    /** @var string */
    protected $src;

    /** @var ?int */
    protected $widthMm;

    /** @var string */
    protected $align;

    public function __construct(string $src, ?int $widthMm = null, string $align = 'left')
    {
        $this->src = $src;
        $this->widthMm = $widthMm;
        $this->align = in_array($align, ['center', 'right']) ? $align : 'left';
    }

    public function render(): string
    {
        $src = $this->resolveSrc();
        if (empty($src)) {
            return '';
        }

        $style = $this->widthMm > 0 ? ' style="width: ' . $this->widthMm . 'mm;"' : '';
        return '<div class="' . $this->css('text-' . $this->align) . '">'
            . '<img src="' . $this->escape($src) . '" alt=""' . $style . '/>'
            . '</div>';
    }

    protected function resolveSrc(): string
    {
        if (str_starts_with($this->src, 'data:') || filter_var($this->src, FILTER_VALIDATE_URL)) {
            return $this->src;
        }

        $path = file_exists($this->src) ? $this->src : FS_FOLDER . '/' . ltrim($this->src, '/');
        if (false === file_exists($path) || false === is_file($path)) {
            return '';
        }

        $mime = mime_content_type($path);
        if (false === str_starts_with((string)$mime, 'image/')) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}
