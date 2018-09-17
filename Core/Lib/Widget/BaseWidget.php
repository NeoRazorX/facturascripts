<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of BaseWidget
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class BaseWidget extends VisualItem
{

    /**
     *
     * @var array
     */
    protected static $assets = [];

    /**
     *
     * @var string
     */
    public $fieldname;

    /**
     *
     * @var string
     */
    public $icon;

    /**
     *
     * @var string
     */
    public $onclick;

    /**
     *
     * @var array
     */
    public $options = [];

    /**
     *
     * @var bool
     */
    public $readonly;

    /**
     *
     * @var bool
     */
    public $required;

    /**
     *
     * @var mixed
     */
    protected $value;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->fieldname = $data['fieldname'];
        $this->icon = isset($data['icon']) ? $data['icon'] : '';
        $this->onclick = isset($data['onclick']) ? $data['onclick'] : '';
        $this->readonly = isset($data['readonly']);
        $this->required = isset($data['required']);
        $this->loadOptions($data['children']);
    }

    /**
     *
     * @param object $model
     * @param string $title
     * @param string $description
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '')
    {
        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . static::$i18n->trans($description) . '</small>';
        $inputHtml = $this->inputHtml();
        $labelHtml = '<label>' . static::$i18n->trans($title) . '</label>';

        if (empty($this->icon)) {
            return '<div class="form-group">'
                . $labelHtml
                . $inputHtml
                . $descriptionHtml
                . '</div>';
        }

        return '<div class="form-group">'
            . $labelHtml
            . '<div class="input-group">'
            . '<div class="input-group-prepend">'
            . '<span class="input-group-text"><i class="fas ' . $this->icon . ' fa-fw"></i></span>'
            . '</div>'
            . $inputHtml
            . '</div>'
            . $descriptionHtml
            . '</div>';
    }

    /**
     *
     * @return array
     */
    public static function getAssets()
    {
        return static::$assets;
    }

    /**
     *
     * @param object $model
     *
     * @return string
     */
    public function inputHidden($model)
    {
        $this->setValue($model);
        return '<input type="hidden" name="' . $this->fieldname . '" value="' . $this->value . '"/>';
    }

    /**
     * 
     * @param object $model
     *
     * @return string
     */
    public function plainText($model)
    {
        $this->setValue($model);
        return $this->show();
    }

    /**
     *
     * @param object $model
     * @param string $display
     *
     * @return string
     */
    public function tableCell($model, $display = 'left')
    {
        $this->setValue($model);
        $class = 'text-' . $display;
        return '<td class="' . $this->tableCellClass($class) . '">' . $this->onclickHtml($this->show()) . '</td>';
    }

    /**
     *
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = '')
    {
        $class = empty($extraClass) ? 'form-control' : 'form-control ' . $extraClass;
        return '<input type="' . $type . '" name="' . $this->fieldname . '" value="' . $this->value
            . '" class="' . $class . '"' . $this->inputHtmlExtraParams() . '/>';
    }

    /**
     *
     * @return string
     */
    protected function inputHtmlExtraParams()
    {
        $params .= $this->readonly ? ' readonly=""' : '';
        $params .= $this->required ? ' required=""' : '';

        return $params;
    }

    /**
     *
     * @param array $children
     */
    protected function loadOptions($children)
    {
        foreach ($children as $child) {
            if ($child['tag'] === 'option') {
                $child['text'] = html_entity_decode($child['text']);
                $this->options[] = $child;
            }
        }
    }

    /**
     *
     * @param string $inside
     *
     * @return string
     */
    protected function onclickHtml($inside)
    {
        if (empty($this->onclick) || is_null($this->value)) {
            return $inside;
        }

        return '<a href="' . $this->onclick . '?code=' . rawurlencode($this->value) . '" class="cancelClickable">' . $inside . '</a>';
    }

    /**
     *
     * @param object $model
     */
    protected function setValue($model)
    {
        if (isset($model->{$this->fieldname})) {
            $this->value = $model->{$this->fieldname};
        } elseif (isset($model->properties) && isset($model->properties[$this->fieldname])) {
            $this->value = $model->properties[$this->fieldname];
        } else {
            $this->value = null;
        }
    }

    /**
     *
     * @return string
     */
    protected function show()
    {
        return is_null($this->value) ? '-' : (string) $this->value;
    }

    /**
     *
     * @param string $initialClass
     * @param string $alternativeClass
     *
     * @return string
     */
    protected function tableCellClass($initialClass = '', $alternativeClass = '')
    {
        foreach ($this->options as $opt) {
            $apply = false;
            switch ($opt['text'][0]) {
                case '<':
                    $matchValue = substr($opt['text'], 1) ?: '';
                    $apply = ((float) $this->value < (float) $matchValue);
                    break;

                case '>':
                    $matchValue = substr($opt['text'], 1) ?: '';
                    $apply = ((float) $this->value > (float) $matchValue);
                    break;

                default:
                    $apply = ($opt['text'] == $this->value);
                    break;
            }

            if ($apply) {
                $alternativeClass = $this->colorToClass($opt['color'], 'text-');
                break;
            }
        }

        $class = [$initialClass];
        if (!empty($alternativeClass)) {
            $class[] = $alternativeClass;
        } elseif (is_null($this->value)) {
            $class[] = 'table-warning';
        }

        return implode(' ', $class);
    }
}
