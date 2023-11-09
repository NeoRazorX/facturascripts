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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Tools;

abstract class PdfEngine
{
    /** @var array */
    protected $data = [];

    /** @var string */
    protected $lang = '';

    abstract public function addCompanyHeader(int $id_empresa);

    abstract public function addHtml(string $html);

    abstract public function addImage(string $filePath);

    abstract public function addModel(ModelClass $model);

    abstract public function addModelList(array $list, array $header = [], array $options = []);

    abstract public function addTable(array $rows, array $header = [], array $options = []);

    abstract public function addText(string $text, array $options = []);

    abstract public static function create(string $size = 'a4', string $orientation = 'portrait');

    abstract public function newPage();

    abstract public function output(): string;

    abstract public function save(string $filePath): bool;

    abstract public function setTitle(string $title);

    abstract public function setOrientation(string $orientation);

    abstract public function setSize(string $size);

    abstract public function showFooter(bool $show);

    abstract public function showHeader(bool $show);

    public function getData(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function setData(string $key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function setLang(string $lang)
    {
        $this->lang = $lang;

        return $this;
    }

    protected function trans(string $text, array $context = []): string
    {
        return Tools::lang($this->lang)->trans($text, $context);
    }
}
