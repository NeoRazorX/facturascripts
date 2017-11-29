<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of ColumnItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ColumnItem extends VisualItem implements VisualItemInterface
{

    /**
     * Additional text that explains the field to the user
     *
     * @var string
     */
    public $description;

    /**
     * State and alignment of the display configuration
     * (left|right|center|none)
     *
     * @var string
     */
    public $display;

    /**
     * Field display object configuration
     *
     *
     * @var mixed
     */
    public $widget;

    /**
     * Create and load the structure of a column based on an XML file
     *
     * @param \SimpleXMLElement $column
     * @return GroupItem
     */
    public static function newFromXML($column)
    {
        $result = new ColumnItem();
        $result->loadFromXML($column);
        return $result;
    }

    /**
     * Create and load the structure of a column based on the database
     *
     * @param array $group
     * @return GroupItem
     */
    public static function newFromJSON($column)
    {
        $result = new ColumnItem();
        $result->loadFromJSON($column);
        return $result;
    }

    /**
     * Constructs and initializes the class
     */
    public function __construct()
    {
        parent::__construct();

        $this->description = '';
        $this->display = 'left';
        $this->widget = NULL;
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $column
     */
    public function loadFromXML($column)
    {
        parent::loadFromXML($column);

        if (empty($this->title)) {
            $this->title = $this->name;
        }

        $column_atributes = $column->attributes();
        $this->description = (string) $column_atributes->description;

        if (!empty($column_atributes->display)) {
            $this->display = (string) $column_atributes->display;
        }

        switch (TRUE) {
            case isset($column->widget):
                $this->widget = WidgetItem::newFromXML($column);
                break;

            case isset($column->button):
                $this->widget = WidgetButton::newFromXML($column->button);
                break;
        }
    }

    /**
     * Loads the attributes structure from a JSON file
     *
     *
     * @param array $column
     */
    public function loadFromJSON($column)
    {
        parent::loadFromJSON($column);
        $this->description = (string) $column['description'];
        $this->display = (string) $column['display'];

        if (!empty($this->widget)) {
            unset($this->widget);
        }
        $this->widget = WidgetItem::newFromJSON($column);
    }

    /**
     * Loads a group of database columns from a JSON file
     *
     * @param array $columns
     *
     * @return array
     */
    public function columnsFromJSON($columns)
    {
        $result = [];
        foreach ($columns as $data) {
            $columnItem = new self();
            $columnItem->loadFromJSON($data);
            $result[] = $columnItem;
        }

        return $result;
    }

    /**
     * check and apply special operations on the columns
     *
     * @return mixed
     */
    public function applySpecialOperations()
    {
        if ($this->widget->type === 'select') {
            if (isset($this->widget->values[0]['source'])) {
                $this->widget->loadValuesFromModel();
                return;
            }

            if (isset($this->widget->values[0]['start'])) {
                $this->widget->loadValuesFromRange();
            }
        }
    }

    /**
     * Generates HTML code for the element's header display
     *
     * @param string $value
     *
     * @return string
     */
    public function getHeaderHTML($value)
    {
        $html = parent::getHeaderHTML($value);

        if (!empty($this->description)) {
            $html .= '<span title="' . $this->i18n->trans($this->description) . '"></span>';
        }

        return $html;
    }

    /**
     * Generates the HTML code to display the model data for the List controllers
     *
     * @param string $value
     *
     * @return string
     */
    public function getListHTML($value)
    {
        return $this->widget->getListHTML($value);
    }

    /**
     * Generates the HTML code to display the data from the model for Edit controllers
     *
     * @param string $value
     * @param bool $withLabel
     * @param string $formName
     *
     * @return string
     */
    public function getEditHTML($value, $withLabel = TRUE, $formName = 'main_form')
    {
        $header = $withLabel ? $this->getHeaderHTML($this->title) : '';
        $data = $this->getColumnData($this->widget->columnFunction());

        switch ($this->widget->type) {
            case 'checkbox':
                $html = $this->checkboxHTMLColumn($header, $value, $data);
                break;

            case 'radio':
                $html = $this->radioHTMLColumn($header, $data, $value);
                break;

            case 'calculate':
            case 'action':
            case 'modal':
                $html = $this->buttonHTMLColumn($data, $formName);
                break;

            default:
                $html = $this->standardHTMLColumn($header, $value, $data);
                break;
        }

        return $html;
    }

    /**
     * Returns the HTML code to display a non special field
     *
     * @param string $header
     * @param string $value
     * @param array $data
     *
     * @return string
     */
    private function standardHTMLColumn($header, $value, $data)
    {
        $label = ($header != null) ? '<label for="' . $this->widget->fieldName . '" ' . $data['ColumnHint'] . '>' . $header . '</label>' : '';
        $input = $this->widget->getEditHTML($value);

        return '<div class="form-group' . $data['ColumnClass'] . '">'
            . $label . $input . $data['ColumnDescription'] . $data['ColumnRequired']
            . '</div>';
    }

    /**
     * Returns the HTML code to display a button
     *
     * @param array $data
     * @param string $formName
     * @return string
     */
    private function buttonHTMLColumn($data, $formName)
    {
        return '<div class="form-group' . $data['ColumnClass'] . '"><label>&nbsp;</label>'
            . $this->widget->getHTML($this->widget->label, $formName, $data['ColumnHint'], 'col')
            . $data['ColumnDescription']
            . '</div>';
    }

    /**
     * Returns the HTML code to display a checkbox field
     *
     * @param string $header
     * @param string $value
     * @param array $data
     *
     * @return string
     */
    private function checkboxHTMLColumn($header, $value, $data)
    {
        $input = $this->widget->getEditHTML($value);
        $label = ($header != null) ? '<label class="form-check-label custom-control custom-checkbox mb-2 mr-sm-2 mb-sm-0" ' . $data['ColumnHint'] . '>' . $input . '&nbsp;' . $header . '</label>' : '';

        $result = '<div class="form-row align-items-center' . $data['ColumnClass'] . '">'
            . '<div class="form-check col">' . $label . $data['ColumnDescription'] . '</div>'
            . $data['ColumnRequired']
            . '</div>';

        return $result;
    }

    /**
     * Returns the HTML code to display a list of options
     *
     * @param string $header
     * @param array $data
     * @param string $value
     *
     * @return string
     */
    private function radioHTMLColumn($header, $data, $value)
    {
        $html = '';
        $index = 0;
        $template_var = ['"sufix%', '"value%', '"checked%'];

        $result = '<div class="' . $data['ColumnClass'] . '">'
            . '<label>' . $header . '</label>';

        $input = $this->widget->getEditHTML($value);
        foreach ($this->widget->values as $optionValue) {
            $checked = ($optionValue['value'] == $value) ? ' checked="checked"' : '';
            ++$index;
            $values = [($index . '"'), $optionValue['value'], $checked];
            $html .= '<div class="form-check">'
                . '<label class="form-check-label custom-control custom-checkbox mb-2 mr-sm-2 mb-sm-0" ' . $data['ColumnHint'] . '>'
                . str_replace($template_var, $values, $input)
                . '&nbsp;' . $optionValue['title']
                . '</label>'
                . '</div>';
        }

        $result .= $html . $data['ColumnRequired'] . '</div>';
        return $result;
    }

    /**
     * Executes the function list ($properties) to get the column properties
     * @param string[] $properties
     *
     * @return array
     */
    private function getColumnData($properties)
    {
        $result = [];
        foreach ($properties as $value) {
            $function = 'get' . $value;
            $result[$value] = $this->$function();
        }

        return $result;
    }

    /**
     * Returns the column class
     *
     * @return string
     */
    protected function getColumnClass()
    {
        return ($this->numColumns > 0) ? (' col-md-' . $this->numColumns) : ' col';
    }

    /**
     * Returns the HTML code to display a popover with the specified string
     *
     * @return string
     */
    protected function getColumnHint()
    {
        return $this->widget->getHintHTML($this->i18n->trans($this->widget->hint));
    }

    /**
     * Returns the HTML code to display if a column is required or not
     *
     * @return string
     */
    protected function getColumnRequired()
    {
        return $this->widget->required ? '<div class="invalid-feedback">' . $this->i18n->trans('please-enter-value') . '</div>' : '';
    }

    /**
     * Returns the HTML code to display a description
     *
     * @return string
     */
    protected function getColumnDescription()
    {
        return empty($this->description) ? '' : '<small class="form-text text-muted">' . $this->i18n->trans($this->description) . '</small>';
    }
}
