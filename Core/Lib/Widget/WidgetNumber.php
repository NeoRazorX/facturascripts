<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\NumberTools;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of WidgetNumber
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class WidgetNumber extends BaseWidget
{

    /**
     * Indicates the number of decimals to use.
     *
     * @var NumberTools
     */
    protected static $numberTools;

    /**
     *
     * @var int
     */
    public $decimal;

    /**
     * Indicates the min value
     *
     * @var string
     */
    protected $min;

    /**
     * Indicates the max value
     *
     * @var string
     */
    protected $max;

    /**
     * Indicates the step value
     *
     * @var string
     */
    protected $step;

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        if (!isset(static::$numberTools)) {
            static::$numberTools = new NumberTools();
        }

        parent::__construct($data);
        $this->decimal = (int) ($data['decimal'] ?? FS_NF0);
        $this->min = $data['min'] ?? '';
        $this->max = $data['max'] ?? '';
        $this->step = $data['step'] ?? 'any';
    }

    /**
     * 
     * @return array
     */
    public function gridFormat(): array
    {
        $format = '0.';
        for ($num = 0; $num < $this->decimal; $num++) {
            $format .= '0';
        }

        return ['pattern' => $format];
    }

    /**
     * 
     * @param object  $model
     * @param Request $request
     */
    public function processFormData(&$model, $request)
    {
        $model->{$this->fieldname} = (float) $request->request->get($this->fieldname);
    }

    /**
     *
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'number', $extraClass = '')
    {
        return parent::inputHtml($type, $extraClass);
    }

    /**
     * Add extra attributes to html input field
     *
     * @return string
     */
    protected function inputHtmlExtraParams()
    {
        $step = ' step="' . $this->step . '"';
        $min = $this->min !== '' ? ' min="' . $this->min . '"' : '';
        $max = $this->max !== '' ? ' max="' . $this->max . '"' : '';
        return $min . $max . $step . parent::inputHtmlExtraParams();
    }

    /**
     * 
     * @param object $model
     */
    protected function setValue($model)
    {
        parent::setValue($model);
        if (null === $this->value && $this->required) {
            $this->value = empty($this->min) ? 0 : (float) $this->min;
        }
    }

    /**
     *
     * @return string
     */
    protected function show()
    {
        return \is_null($this->value) ? '-' : static::$numberTools->format($this->value, $this->decimal);
    }

    /**
     *
     * @param string $initialClass
     * @param string $alternativeClass
     */
    protected function tableCellClass($initialClass = '', $alternativeClass = '')
    {
        $initialClass .= ' text-nowrap';

        if (0 == $this->value) {
            $alternativeClass = 'text-warning';
        } elseif ($this->value < 0) {
            $alternativeClass = 'text-danger';
        }

        return parent::tableCellClass($initialClass, $alternativeClass);
    }
}
