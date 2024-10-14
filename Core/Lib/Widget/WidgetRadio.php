<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class WidgetRadio extends BaseWidget
{
    /** @var CodeModel */
    protected static $codeModel;

    /** @var string */
    protected $fieldcode;

    /** @var string */
    protected $fieldtitle;

    /** @var string */
    protected $source;

    /** @var bool */
    protected $translate;

    /** @var bool */
    protected $images;

    /** @var string */
    protected $imagesPath;

    /** @var array */
    public $values = [];

    /** @param array $data */
    public function __construct($data)
    {
        if (!isset(static::$codeModel)) {
            static::$codeModel = new CodeModel();
        }

        parent::__construct($data);
        $this->translate = isset($data['translate']);
        $this->images = isset($data['images']) && $data['images'] === 'true';
        $this->imagesPath = $data['path'] ?? '';

        if ($this->images && !empty($this->imagesPath)) {
            $this->clearImagesPath();
        }

        foreach ($data['children'] as $child) {
            if ($child['tag'] !== 'values') {
                continue;
            }

            if (isset($child['source'])) {
                $this->setSourceData($child);
                break;
            } elseif (isset($child['start'])) {
                $this->setValuesFromRange($child['start'], $child['end'], $child['step']);
                break;
            }

            $this->setValuesFromArray($data['children'], $this->translate, !$this->required, 'text');
            break;
        }
    }

    /**
     * Obtains the configuration of the datasource used in obtaining data
     */
    public function getDataSource(): array
    {
        return [
            'source' => $this->source,
            'fieldcode' => $this->fieldcode,
            'fieldtitle' => $this->fieldtitle
        ];
    }

    public function getImagePath(): string
    {
        return $this->imagesPath ?? '';
    }

    /**
     * @param object $model
     * @param Request $request
     */
    public function processFormData(&$model, $request): void
    {
        $value = $request->request->get($this->fieldname, '');
        $model->{$this->fieldname} = ('' === $value) ? null : $value;
    }

    /**
     * Loads the value list from a given array.
     * The array must have one of the two following structures:
     * - If it's a value array, it must uses the value of each element as title and value
     * - If it's a multidimensional array, the indexes value and title must be set for each element
     *
     * @param array $items
     * @param bool $translate
     * @param bool $addEmpty
     * @param string $col1
     * @param string $col2
     */
    public function setValuesFromArray(array $items, bool $translate = false, bool $addEmpty = false, string $col1 = 'value', string $col2 = 'title'): void
    {
        $this->values = [];
        foreach ($items as $item) {
            if (false === is_array($item)) {
                $this->values[] = ['value' => $item, 'title' => $item];
                continue;
            } elseif (isset($item['tag']) && $item['tag'] !== 'values') {
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

    public function setValuesFromArrayKeys(array $values, bool $translate = false, bool $addEmpty = false): void
    {
        $this->values = [];
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
     * @param bool $translate
     */
    public function setValuesFromCodeModel(array $rows, bool $translate = false): void
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
     * @param int $start
     * @param int $end
     * @param float $step
     */
    public function setValuesFromRange(int $start, int $end, float $step): void
    {
        $values = range($start, $end, $step);
        $this->setValuesFromArray($values);
    }

    /**
     *  Translate the fixed titles, if they exist
     */
    private function applyTranslations(): void
    {
        foreach ($this->values as $key => $value) {
            if (empty($value['title']) || '------' === $value['title']) {
                continue;
            }

            $this->values[$key]['title'] = Tools::lang()->trans($value['title']);
        }
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets(): void
    {
        AssetManager::addCss(FS_ROUTE . '/Dinamic/Assets/CSS/WidgetRadio.css', 2);
    }

    protected function clearImagesPath(): void
    {
        if (substr($this->imagesPath, 0, 1) == '/' || substr($this->imagesPath, 0, 1) == '\\') {
            $this->imagesPath = substr($this->imagesPath, 1);
        }

        if (substr($this->imagesPath, -1) == '/' || substr($this->imagesPath, -1) == '\\') {
            $this->imagesPath = substr($this->imagesPath, 0, -1);
        }
    }

    /**
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'radio', $extraClass = '')
    {
        if ($this->images) {
            return $this->inputHtmlImages($type, $extraClass);
        }

        $html = '';
        $class = $this->combineClasses($this->css(''), $this->class, $extraClass);
        $readOnly = $this->readonly() ? 'disabled' : '';

        foreach ($this->values as $key => $option) {
            $cont = $key + 1;
            $title = empty($option['title']) ? $option['value'] : $option['title'];

            $check = '';
            // don't use strict comparison (===)
            if ($option['value'] == $this->value) {
                $check = 'checked';
            }

            $firstCss = '';
            if ($cont === 1 && strpos($class, 'form-check-inline') !== false) {
                $firstCss = $class != '' ? ' ms-3' : 'ms-3';
            }

            $html .= '<div class="form-check ' . $class . $firstCss . '">'
                . '<input type="radio" class="form-check-input" id="' . $this->fieldname . $cont . '" name="'
                . $this->fieldname . '" value="' . $option['value'] . '" ' . $check . ' ' . $readOnly . '>'
                . '<label class="form-check-label" for="' . $this->fieldname . $cont . '">'
                . $title
                . '</label>'
                . '</div>';
        }

        return $html;
    }

    protected function inputHtmlImages($type = 'radio', $extraClass = ''): string
    {
        $class = $this->combineClasses($this->css(''), $this->class, $extraClass);
        $html = '<div class="rounded border bg-secondary ' . $class . '">';
        $readOnly = $this->readonly() ? 'disabled' : '';

        foreach ($this->values as $key => $option) {
            $title = empty($option['title']) ? $option['value'] : $option['title'];

            $check = '';
            // don't use strict comparison (===)
            if ($option['value'] == $this->value) {
                $check = 'checked';
            }

            $nameImg = str_replace(' ', '-', $title);
            $url = FS_ROUTE . DIRECTORY_SEPARATOR . $this->imagesPath . DIRECTORY_SEPARATOR . $nameImg . '.png';
            $html .= '<div class="widget-radio d-inline-block p-2">'
                . '<input type="radio" id="' . $nameImg . '" class="d-none imgbgchk" name="' . $this->fieldname
                . '" value="' . $option['value'] . '" ' . $check . ' ' . $readOnly . '>'
                . '<label class="mb-0" for="' . $nameImg . '">'
                . '<img src="' . $url . '" />'
                . '<div class="tick_container">'
                . '<div class="tick"><i class="fa-solid fa-check"></i></div>'
                . '</div>'
                . '</label>'
                . '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Set datasource data and Load data from Model into values array.
     *
     * @param array $child
     * @param bool $loadData
     */
    protected function setSourceData(array $child, bool $loadData = true): void
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
     * @return string
     */
    protected function show()
    {
        if (null === $this->value) {
            return '-';
        }

        $selected = null;
        foreach ($this->values as $option) {
            // don't use strict comparison (===)
            if ($option['value'] == $this->value) {
                $selected = $option['title'];
            }
        }

        if (null === $selected) {
            // value is not in $this->values
            $selected = static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle);
            $this->values[] = [
                'value' => $this->value,
                'title' => $selected
            ];
        }

        return $selected;
    }
}
