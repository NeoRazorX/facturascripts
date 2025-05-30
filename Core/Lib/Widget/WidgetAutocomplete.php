<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;

/**
 * Description of WidgetAutocomplete
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WidgetAutocomplete extends WidgetSelect
{
    /**
     * Name of the field by which it is filtered.
     *
     * @var string
     */
    protected $fieldfilter;

    /**
     * Descriptive text of the selected value
     *
     * @var string
     */
    protected $selected = null;

    /**
     * Indicates whether a value should be selected strictly from the list
     * of values or whether the user can enter a new or different value
     * from the list.
     *
     * @var bool
     */
    public $strict = true;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->strict = isset($data['strict']) ? ($data['strict'] == 'true') : true;
    }

    /**
     * @param object $model
     * @param string $title
     * @param string $description
     * @param string $titleurl
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . Tools::lang()->trans($description) . '</small>';
        $inputHtml = $this->inputHtml();
        $labelHtml = '<label class="mb-0">' . $this->onclickHtml(Tools::lang()->trans($title), $titleurl) . '</label>';

        if ('' === $this->value || null === $this->value) {
            return '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>'
                . '<div class="mb-3">'
                . $labelHtml
                . '<div class="input-group">'
                . '<span class="input-group-text"><i class="fa-solid fa-search fa-fw"></i></span>'
                . $inputHtml
                . '</div>'
                . $descriptionHtml
                . '</div>';
        }

        return '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>'
            . '<div class="mb-3">'
            . $labelHtml
            . '<div class="input-group">'
            . $this->inputGroupClearBtn()
            . $inputHtml
            . '</div>'
            . $descriptionHtml
            . '</div>';
    }

    /**
     * Set a descriptive text for the selected value
     *
     * @param string $text
     */
    public function setSelected($text)
    {
        $this->selected = $text;
    }

    /**
     * Get the descriptive text of the selected value
     *
     * @return string
     */
    protected function getSelected()
    {
        return empty($this->selected) ? static::$codeModel->getDescription($this->source, $this->fieldcode, $this->value, $this->fieldtitle) : $this->selected;
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
        AssetManager::addCss(FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.css', 2);
        AssetManager::addJs(FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.js', 2);
        AssetManager::addJs(FS_ROUTE . '/Dinamic/Assets/JS/WidgetAutocomplete.js');
    }

    /**
     * @return string
     */
    protected function inputGroupClearBtn()
    {
        if ($this->readonly()) {
            return '<span class="input-group-text"><i class="fa-solid fa-search fa-fw"></i></span>';
        }

        return '<button class="btn btn-spin-action btn-warning" type="button" onclick="this.form.' . $this->fieldname . '.value = \'\'; this.form.onsubmit(); this.form.submit();">'
            . '<i class="fa-solid fa-times" aria-hidden="true"></i>'
            . '</button>';
    }

    /**
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = 'widget-autocomplete')
    {
        $class = $this->combineClasses($this->css('form-control'), $this->class, $extraClass);
        return '<input type="' . $type . '" value="' . $this->getSelected() . '" class="' . $class . '"'
            . ' data-field="' . $this->fieldname . '"'
            . ' data-source="' . $this->source . '"'
            . ' data-fieldcode="' . $this->fieldcode . '"'
            . ' data-fieldtitle="' . $this->fieldtitle . '"'
            . ' data-fieldfilter="' . $this->fieldfilter . '"'
            . ' data-strict="' . $this->strictStr() . '"'
            . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     * Set datasource data and Load data from Model into values array
     */
    protected function setSourceData(array $child, bool $loadData = true)
    {
        // The values are filled in automatically by the view controller
        // according to the information entered by the user.
        parent::setSourceData($child, false);
        $this->fieldfilter = $child['fieldfilter'] ?? '';
    }

    /**
     * @return string
     */
    protected function strictStr()
    {
        return $this->strict ? '1' : '0';
    }
}
