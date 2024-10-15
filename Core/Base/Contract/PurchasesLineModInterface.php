<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\Contract;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\PurchaseDocumentLine;

/**
 * Interface for classes that modify purchase lines.
 *
 * @deprecated replaced by Core/Contract/PurchasesLineModInterface
 */
interface PurchasesLineModInterface
{
    public function apply(PurchaseDocument &$model, array &$lines, array $formData);

    public function applyToLine(array $formData, PurchaseDocumentLine &$line, string $id);

    public function assets(): void;

    public function getFastLine(PurchaseDocument $model, array $formData): ?PurchaseDocumentLine;

    public function map(array $lines, PurchaseDocument $model): array;

    public function newFields(): array;

    public function newModalFields(): array;

    public function newTitles(): array;

    public function renderField(Translator $i18n, string $idlinea, PurchaseDocumentLine $line, PurchaseDocument $model, string $field): ?string;

    public function renderTitle(Translator $i18n, PurchaseDocument $model, string $field): ?string;
}
