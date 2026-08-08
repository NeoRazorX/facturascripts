<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Contract;

interface ExportPDFInterface
{
    public const SECTION_BEFORE_HEADER = 'before-header';
    public const SECTION_AFTER_HEADER = 'after-header';
    public const SECTION_AFTER_SUBJECT = 'after-subject';
    public const SECTION_AFTER_LINES = 'after-lines';
    public const SECTION_AFTER_TOTALS = 'after-totals';
    public const SECTION_FOOTER = 'footer';
    public const SECTION_WATERMARK = 'watermark';
    public const SECTION_EXTRA_PAGES = 'extra-pages';

    public static function create(): self;

    public static function addMod(ExportPDFModInterface $mod, int $priority = 100): void;

    public static function clearMods(): void;

    public static function addModelExtension(string $modelClass, callable $handler, int $priority = 100): void;

    public static function clearModelExtensions(?string $modelClass = null): void;

    public function addModel($model): self;

    public function addTable(array $rows, array $headers = [], array $options = []): self;

    public function addText(string $text, array $options = []): self;

    public function addImage(string $path, array $options = []): self;

    public function addSection(string $position, callable $handler): self;

    public function newPage(?string $orientation = null, bool $force = false): self;

    public function output(): string;

    public function save(string $fileName, ?string $directory = null): string;

    public function setLang(string $langcode): self;

    public function setOrientation(string $orientation): self;

    public function setSize(string $size): self;

    public function setCompany($company): self;

    public function setData(string $key, $value): self;

    public function disableHeader(bool $disabled = true): self;

    public function disableFooter(bool $disabled = true): self;
}
