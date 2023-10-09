<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ListFilter;

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Translator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of BaseFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BaseFilter
{

    /**
     * Submit form on every filter change.
     *
     * @var bool
     */
    public $autosubmit;

    /**
     * Field name.
     *
     * @var string
     */
    public $field;

    /**
     * Translator object.
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * Filter key.
     *
     * @var string
     */
    public $key;

    /**
     * Label to show on this filter.
     *
     * @var string
     */
    public $label;

    /**
     * @var int
     */
    public $ordernum;

    /**
     * @var bool
     */
    public $readonly = false;

    /**
     * @var int
     */
    private static $totalnum = 0;

    /**
     * @var mixed
     */
    protected $value;

    abstract public function getDataBaseWhere(array &$where): bool;

    abstract public function render(): string;

    /**
     * @param string $key
     * @param string $field
     * @param string $label
     */
    public function __construct(string $key, string $field = '', string $label = '')
    {
        if (!isset(static::$i18n)) {
            static::$i18n = new Translator();
        }

        $this->key = $key;
        $this->field = empty($field) ? $this->key : $field;
        $this->label = empty($label) ? $this->field : $label;
        $this->ordernum = ++self::$totalnum;
        $this->autosubmit = false;
        $this->assets();
    }

    /**
     * Get the filter value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'filter' . $this->key;
    }

    /**
     * Set value to filter
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = Utils::noHtml($value);
    }

    /**
     * Set value to filter from form request
     *
     * @param Request $request
     */
    public function setValueFromRequest(Request &$request)
    {
        $this->setValue($request->request->get($this->name()));
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
    }

    /**
     * @return string
     */
    protected function onChange(): string
    {
        return $this->autosubmit ? ' onchange="this.form.submit();"' : '';
    }

    /**
     * @return string
     */
    protected function readonly(): string
    {
        return $this->readonly ? ' readonly="" disabled=""' : '';
    }
}
