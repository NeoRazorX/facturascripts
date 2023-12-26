<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DbQuery;

/**
 * The class from which all models inherit, connects to the database,
 * check the structure of the table and if necessary create or adapt.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
interface LegacyModelInterface
{
    public static function addExtension($extension): void;

    public function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50): array;

    public function changePrimaryColumnValue($newValue): bool;

    public function clear();

    public function codeModelAll(string $fieldCode = ''): array;

    public function codeModelSearch(string $query, string $fieldCode = '', array $where = []): array;

    public function count(array $where = []): int;

    public function delete(): bool;

    public function exists(): bool;

    public function get($code);

    public function getModelFields(): array;

    public function install(): string;

    public function loadFromCode($code, array $where = [], array $order = []): bool;

    public function loadFromData(array $data = [], array $exclude = []): void;

    public function modelClassName(): string;

    public function newCode(string $field = '', array $where = []);

    public static function primaryColumn(): string;

    public function primaryColumnValue();

    public function primaryDescriptionColumn(): string;

    public function primaryDescription(): string;

    public function save(): bool;

    public static function table(): DbQuery;

    public static function tableName(): string;

    public function test(): bool;

    public function toArray(): array;

    public function url(string $type = 'auto', string $list = 'List'): string;
}
