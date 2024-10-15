<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\PurchaseDocumentLine;

interface PurchasesLineModInterface
{
    public function apply(PurchaseDocument &$model, array &$lines, array $formData): void;

    public function applyToLine(array $formData, PurchaseDocumentLine &$line, string $id): void;

    public function assets(): void;

    public function getFastLine(PurchaseDocument $model, array $formData): ?PurchaseDocumentLine;

    public function map(array $lines, PurchaseDocument $model): array;

    public function newFields(): array;

    public function newModalFields(): array;

    public function newTitles(): array;

    public function renderField(string $idlinea, PurchaseDocumentLine $line, PurchaseDocument $model, string $field): ?string;

    public function renderTitle(PurchaseDocument $model, string $field): ?string;
}
