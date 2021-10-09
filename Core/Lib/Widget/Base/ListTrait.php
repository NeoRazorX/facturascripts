<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Widget\Base;

/**
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
trait ListTrait
{

    /**
     *
     * @var CodeModel
     */
    protected static $codeModel;

    /**
     *
     * @var string
     */
    protected $fieldcode;

    /**
     *
     * @var string
     */
    protected $source;

    /**
     *
     * @var bool
     */
    protected $translate;

    /**
     *
     * @var array
     */
    public $values = [];


    /**
     * Set datasource data and Load data from Model into values array.
     *
     * @param array $child
     * @param bool  $loadData
     */
    protected function setSourceData(array $child, bool $loadData = true)
    {
        $this->source = $child['source'];
        $this->fieldcode = $child['fieldcode'] ?? 'id';
        $this->fieldtitle = $child['fieldtitle'] ?? $this->fieldcode;
        if ($loadData) {
            $values = static::$codeModel->all($this->source, $this->fieldcode, $this->fieldtitle, !$this->required);
            $this->setValuesFromCodeModel($values, $this->translate);
        }
    }

    /**
     * Loads the value list from a given array.
     * The array must have one of the two following structures:
     * - If it's a value array, it must uses the value of each element as title and value
     * - If it's a multidimensional array, the indexes value and title must be set for each element
     *
     * @param array  $items
     * @param bool   $translate
     * @param bool   $addEmpty
     * @param string $col1
     * @param string $col2
     */
    public function setValuesFromArray($items, $translate = false, $addEmpty = false, $col1 = 'value', $col2 = 'title')
    {
        $this->values = $addEmpty ? [['value' => null, 'title' => '------']] : [];
        foreach ($items as $item) {
            if (false === \is_array($item)) {
                $this->values[] = ['value' => $item, 'title' => $item];
                continue;
            }

            if (isset($item['tag']) && $item['tag'] !== 'values') {
                continue;
            }

            if (isset($item[$col1])) {
                $this->values[] = [
                    'value' => $item[$col1],
                    'title' => isset($item[$col2]) ? $item[$col2] : $item[$col1]
                ];
            }
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     *
     * @param array $values
     * @param bool  $translate
     * @param bool  $addEmpty
     */
    public function setValuesFromArrayKeys($values, $translate = false, $addEmpty = false)
    {
        $this->values = $addEmpty ? [['value' => null, 'title' => '------']] : [];
        foreach ($values as $key => $value) {
            $this->values[] = [
                'value' => $key,
                'title' => $value
            ];
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     * Loads the value list from an array with value and title (description)
     *
     * @param array $rows
     * @param bool  $translate
     */
    public function setValuesFromCodeModel($rows, $translate = false)
    {
        $this->values = [];
        foreach ($rows as $codeModel) {
            $this->values[] = [
                'value' => $codeModel->code,
                'title' => $codeModel->description
            ];
        }

        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     *  Translate the fixed titles, if they exist
     */
    private function applyTranslations()
    {
        foreach ($this->values as $key => $value) {
            if (empty($value['title']) || '------' === $value['title']) {
                continue;
            }

            $this->values[$key]['title'] = static::$i18n->trans($value['title']);
        }
    }
}
