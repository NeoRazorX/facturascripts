<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

/**
 * This WidgetItem class modelises the common data and method of a WidgetItem element.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
abstract class WidgetItem implements VisualItemInterface
{

    /**
     * To put the focus on the first editable input.
     *
     * @var bool
     */
    private static $autofocus = true;

    /**
     * Field name with the data that the widget displays
     *
     * @var string
     */
    public $fieldName;

    /**
     * Additional information for the user
     *
     * @var string
     */
    public $hint;

    /**
     * Icon used as a value or to accompany the widget
     *
     * @var string
     */
    public $icon;

    /**
     * Indicates that the max length of value
     *
     * @var int
     */
    public $maxLength;

    /**
     * Destination controller to go to when the displayed data is clicked
     *
     * @var string
     */
    public $onClick;

    /**
     * Visual options to configure the widget
     *
     * @var array
     */
    public $options;

    /**
     * Indicates that the field is read only
     *
     * @var bool
     */
    public $readOnly;

    /**
     * Indicates that the field is mandatory and it must have a value
     *
     * @var bool
     */
    public $required;

    /**
     * Type of widget displayed
     *
     * @var string
     */
    public $type;

    /**
     * Generates the html code to display the model data for Edit controller
     *
     * @param string $value
     */
    abstract public function getEditHTML($value);

    /**
     * Generates the html code to display the model data for List controller
     *
     * @param string $value
     */
    abstract public function getListHTML($value);

    /**
     * WidgetItem constructor.
     */
    public function __construct()
    {
        $this->fieldName = '';
        $this->hint = '';
        $this->icon = null;
        $this->maxLength = 0;
        $this->onClick = '';
        $this->options = [];
        $this->readOnly = false;
        $this->required = false;
    }

    /**
     * Array with list of personalization functions of the column
     */
    public function columnFunction()
    {
        return ['ColumnClass', 'ColumnHint', 'ColumnRequired', 'ColumnDescription'];
    }

    /**
     * Generate the html code to visualize the visual element header
     *
     * @param string $value
     *
     * @return string
     */
    public function getHeaderHTML($value)
    {
        return '<span title="' . $value . '"></span>';
    }

    /**
     * Returns the HTML code to display a popover with the given text
     *
     * @param string $hint
     *
     * @return string
     */
    public function getHintHTML($hint)
    {
        return empty($hint) ? '' : ' data-toggle="popover" data-placement="auto" data-trigger="hover" data-content="'
            . $hint . '" ';
    }

    /**
     * Loads the attributes structure from the database
     *
     * @param array $widget
     */
    public function loadFromJSON($widget)
    {
        $this->fieldName = (string) $widget['fieldName'];
        $this->hint = (string) $widget['hint'];
        $this->readOnly = (bool) $widget['readOnly'];
        $this->required = (bool) $widget['required'];
        $this->maxLength = (integer) $widget['maxLength'];
        $this->icon = (string) $widget['icon'];
        $this->onClick = (string) $widget['onClick'];
        $this->options = (array) $widget['options'];
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $column
     */
    public function loadFromXML($column)
    {
        $widgetAtributes = $column->widget->attributes();
        $this->fieldName = (string) $widgetAtributes->fieldname;
        $this->hint = (string) $widgetAtributes->hint;
        $this->readOnly = (bool) $widgetAtributes->readonly;
        $this->required = (bool) $widgetAtributes->required;
        $this->maxLength = (integer) $widgetAtributes->maxlength;
        $this->icon = (string) $widgetAtributes->icon;
        $this->onClick = (string) $widgetAtributes->onclick;

        $this->getAttributesGroup($this->options, $column->widget->option);
    }

    /**
     * Creates and loads the attributes structure from the database
     *
     * @param array $widget
     *
     * @return WidgetItem
     */
    public static function newFromJSON($widget)
    {
        $type = (string) $widget['type'];
        $widgetItem = self::widgetItemFromType($type);
        $widgetItem->loadFromJSON($widget);

        return $widgetItem;
    }

    /**
     * Creates and loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $column
     *
     * @return WidgetItem
     */
    public static function newFromXML($column)
    {
        $widgetAtributes = $column->widget->attributes();
        $type = (string) $widgetAtributes->type;
        $widget = self::widgetItemFromType($type);
        $widget->loadFromXML($column);

        return $widget;
    }

    /**
     * Indicates if the conditions to apply an Option Text are met
     *
     * @param string $optionValue
     * @param string $valueItem
     *
     * @return bool
     */
    private function canApplyOptions($optionValue, $valueItem)
    {
        switch ($optionValue[0]) {
            case '<':
                $optionValue = substr($optionValue, 1) ?: '';
                $result = ((float) $valueItem < (float) $optionValue);
                break;

            case '>':
                $optionValue = substr($optionValue, 1) ?: '';
                $result = ((float) $valueItem > (float) $optionValue);
                break;

            default:
                $result = ($optionValue === $valueItem);
                break;
        }

        return $result;
    }

    /**
     * Loads the attribute dictionary for a widget's group of options or values
     *
     * @param array               $property
     * @param \SimpleXMLElement[] $group
     */
    protected function getAttributesGroup(&$property, $group)
    {
        $property = [];
        foreach ($group as $item) {
            $values = [];
            foreach ($item->attributes() as $attributeKey => $attributeValue) {
                $values[$attributeKey] = (string) $attributeValue;
            }
            $values['value'] = (string) $item;
            $property[] = $values;
        }
    }

    /**
     * Generates the HTML code to display an icon on the left side of the data
     *
     * @return string
     */
    protected function getIconHTML()
    {
        if (empty($this->icon)) {
            return '';
        }

        $html = '<div class="input-group"><span class="input-group-prepend"><span class="input-group-text">';
        if (strpos($this->icon, 'fa-') === 0) {
            return $html . '<i class="fa ' . $this->icon . '" aria-hidden="true"></i></span></span>';
        }

        return $html . '<i aria-hidden="true">' . $this->icon . '</i></span></span>';
    }

    /**
     * Generates the CSS code for the widget style from the options
     *
     * @param string $valueItem
     *
     * @return string
     */
    protected function getTextOptionsHTML($valueItem)
    {
        $html = '';
        foreach ($this->options as $option) {
            if ($this->canApplyOptions($option['value'], $valueItem)) {
                $html = ' style="';
                foreach ($option as $key => $value) {
                    if ($key !== 'value') {
                        $html .= $key . ':' . $value . '; ';
                    }
                }
                $html .= '"';
                break;
            }
        }

        return $html;
    }

    /**
     * Generates the HTML code for special attributes like:
     * hint
     * read only
     * mandatory value
     *
     * @return string
     */
    protected function specialAttributes()
    {
        $attributes = $this->getHintHTML($this->hint);
        $attributes .= empty($this->readOnly) ? '' : ' readonly=""';
        $attributes .= empty($this->required) ? '' : ' required=""';
        $attributes .= empty($this->maxLength) ? '' : ' maxlength="' . $this->maxLength . '"';

        if (!$this->readOnly && self::$autofocus) {
            $attributes .= ' autofocus=""';
            self::$autofocus = false;
        }

        return $attributes;
    }

    /**
     * Returns the HTML code to edit non special controls
     *
     * @param string $value
     * @param string $specialAttributes
     * @param string $extraClass
     * @param string $type
     *
     * @return string
     */
    protected function standardEditHTMLWidget($value, $specialAttributes, $extraClass = '', $type = '')
    {
        $type2 = empty($type) ? $this->type : $type;
        $html = $this->getIconHTML()
            . '<input type="' . $type2 . '" class="form-control' . $extraClass . '"'
            . 'name="' . $this->fieldName . '" value="' . $value . '"' . $specialAttributes . ' />';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Returns the HTML code for the list of non special controls
     *
     * @param string $value
     * @param string $text
     *
     * @return string
     */
    protected function standardListHTMLWidget($value, $text = '')
    {
        if ($value === null || $value === '') {
            return '';
        }

        $text2 = empty($text) ? $value : $text;
        $style = $this->getTextOptionsHTML($value);
        return empty($this->onClick) ? '<span' . $style . '>' . $text2 . '</span>' : '<a href="' . $this->onClick
            . '?code=' . $value . '" ' . $style . '>' . $text2 . '</a>';
    }

    /**
     * Class dynamic constructor. It creates a widget of the given type
     *
     * @param string $type
     *
     * @return WidgetItem
     */
    private static function widgetItemFromType($type)
    {
        switch ($type) {
            case 'autocomplete':
                return new WidgetItemAutocomplete();

            case 'checkbox':
                return new WidgetItemCheckBox();

            case 'color':
                return new WidgetItemColor();

            case 'date':
            case 'datepicker':
                return new WidgetItemDateTime();

            case 'filechooser':
                return new WidgetItemFileChooser();

            case 'money':
                return new WidgetItemMoney();

            case 'number':
                return new WidgetItemNumber();

            case 'radio':
                return new WidgetItemRadio();

            case 'select':
                return new WidgetItemSelect();

            default:
                return new WidgetItemText($type);
        }
    }
}
