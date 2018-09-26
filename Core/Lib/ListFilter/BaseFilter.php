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
namespace FacturaScripts\Core\Lib\ListFilter;

use FacturaScripts\Core\Base\Translator;

/**
 * Description of BaseFilter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BaseFilter
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
    public $field;

    /**
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     *
     * @var string
     */
    public $key;

    /**
     *
     * @var string
     */
    public $label;

    /**
     *
     * @var mixed
     */
    public $value;

    abstract public function getDataBaseWhere(array &$where): bool;

    abstract public function render();

    /**
     *
     * @param string $key
     * @param string $field
     * @param string $label
     */
    public function __construct($key, $field = '', $label = '')
    {
        if (!isset(static::$i18n)) {
            static::$i18n = new Translator();
        }

        $this->key = $key;
        $this->field = empty($field) ? $this->key : $field;
        $this->label = empty($label) ? $this->field : $label;
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
     * @return string
     */
    public function name()
    {
        return 'filter' . $this->key;
    }
}
