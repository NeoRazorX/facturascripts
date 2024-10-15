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

interface PurchasesModInterface
{
    public function apply(PurchaseDocument &$model, array $formData): void;

    public function applyBefore(PurchaseDocument &$model, array $formData): void;

    public function assets(): void;

    public function newBtnFields(): array;

    public function newFields(): array;

    public function newModalFields(): array;

    public function renderField(PurchaseDocument $model, string $field): ?string;
}
