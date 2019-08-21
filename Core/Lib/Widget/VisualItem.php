<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Translator;

/**
 * Description of VisualItem
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class VisualItem
{

    /**
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * Identifies the object with a defined name in the view
     *
     * @var string
     */
    public $id;

    /**
     * Selected security level.
     *
     * @var int
     */
    private static $level = 0;

    /**
     * Name defined in the view as key
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var int
     */
    protected static $uniqueId = -1;

    /**
     *
     */
    public function __construct($data)
    {
        if (!isset(static::$i18n)) {
            static::$i18n = new Translator();
        }

        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
    }

    /**
     * 
     * @return int
     */
    public static function getLevel()
    {
        return self::$level;
    }

    /**
     * 
     * @param int $new
     */
    public static function setLevel($new)
    {
        self::$level = $new;
    }

    /**
     *
     * @param string $color
     * @param string $prefix
     *
     * @return string
     */
    protected function colorToClass($color, $prefix)
    {
        switch ($color) {
            case 'danger':
            case 'dark':
            case 'info':
            case 'light':
            case 'outline-danger':
            case 'outline-dark':
            case 'outline-info':
            case 'outline-light':
            case 'outline-primary':
            case 'outline-secondary':
            case 'outline-success':
            case 'outline-warning':
            case 'primary':
            case 'secondary':
            case 'success':
            case 'warning':
                return $prefix . $color;
        }

        return '';
    }

    /**
     * Calculate color from option configuration
     *
     * @param string[] $option
     * @param mixed    $value
     * @param string   $prefix
     *
     * @return string
     */
    public function getColorFromOption($option, $value, $prefix): string
    {
        $applyOperator = '';
        $operators = ['>', 'gt:', 'gte:', '<', 'lt:', 'lte:', '!', 'neq:', 'like:', 'null:', 'notnull:'];
        foreach ($operators as $operator) {
            if (0 === strpos($option['text'], $operator)) {
                $applyOperator = $operator;
                break;
            }
        }

        $matchValue = substr($option['text'], strlen($applyOperator));
        $apply = $matchValue == $value;

        switch ($applyOperator) {
            case '>':
            case 'gt:':
                $apply = (float) $value > (float) $matchValue;
                break;

            case 'gte:':
                $apply = (float) $value >= (float) $matchValue;
                break;

            case '<':
            case 'lt:':
                $apply = (float) $value < (float) $matchValue;
                break;

            case 'lte:':
                $apply = (float) $value <= (float) $matchValue;
                break;

            case '!':
            case 'neq:':
                $apply = $value != $matchValue;
                break;

            case 'like:':
                $apply = false !== stripos($value, $matchValue);
                break;

            case 'null:':
                $apply = null === $value;
                break;

            case 'notnull:':
                $apply = null !== $value;
                break;
        }

        return $apply ? $this->colorToClass($option['color'], $prefix) : '';
    }

    /**
     * Returns equivalent css class to $class. To extend in plugins.
     *
     * @param string $class
     *
     * @return string
     */
    protected function css($class)
    {
        return $class;
    }

    /**
     *
     * @return int
     */
    protected function getUniqueId()
    {
        static::$uniqueId++;
        return static::$uniqueId;
    }
}
