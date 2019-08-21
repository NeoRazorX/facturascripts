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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model;
use SimpleXMLElement;

/**
 * Description of VisualItemLoadEngine
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class VisualItemLoadEngine
{

    /**
     *
     * @var string
     */
    private static $namespace = '\\FacturaScripts\\Dinamic\\Lib\\Widget\\';

    /**
     * 
     * @return string
     */
    public static function getNamespace()
    {
        return self::$namespace;
    }

    /**
     * 
     * @param string $namespace
     */
    public static function setNamespace($namespace)
    {
        self::$namespace = $namespace;
    }

    /**
     * Loads an xmlview data into a PageOption model.
     *
     * @param string           $name
     * @param Model\PageOption $model
     *
     * @return boolean
     */
    public static function installXML($name, &$model)
    {
        $model->name = $name;

        $fileName = \FS_FOLDER . '/Dinamic/XMLView/' . $name . '.xml';
        if (\FS_DEBUG && !file_exists($fileName)) {
            $fileName = \FS_FOLDER . '/Core/XMLView/' . $name . '.xml';
        }

        if (!file_exists($fileName)) {
            static::saveError('error-processing-xmlview', ['%fileName%' => 'XMLView\\' . $name . '.xml']);
            return false;
        }

        $xml = simplexml_load_string(file_get_contents($fileName));
        if ($xml === false) {
            static::saveError('error-processing-xmlview', ['%fileName%' => 'XMLView\\' . $name . '.xml']);
            return false;
        }

        /// turns xml into an array
        $array = static::xmlToArray($xml);
        $model->columns = [];
        $model->modals = [];
        $model->rows = [];
        foreach ($array['children'] as $value) {
            switch ($value['tag']) {
                case 'columns':
                    $model->columns = $value['children'];
                    break;

                case 'modals':
                    $model->modals = $value['children'];
                    break;

                case 'rows':
                    $model->rows = $value['children'];
                    break;
            }
        }

        return true;
    }

    /**
     * Reads PageOption data and loads groups, columns, rows and widgets into selected arrays.
     *
     * @param array            $columns
     * @param array            $modals
     * @param array            $rows
     * @param Model\PageOption $model
     */
    public static function loadArray(&$columns, &$modals, &$rows, $model)
    {
        static::getGroupsColumns($model->columns, $columns);
        static::getGroupsColumns($model->modals, $modals);

        foreach ($model->rows as $name => $item) {
            $className = static::getNamespace() . 'Row' . ucfirst($name);
            if (class_exists($className)) {
                $rowItem = new $className($item);
                $rows[$name] = $rowItem;
            }
        }

        /// we allways need a row type actions
        $className = static::getNamespace() . 'RowActions';
        if (!isset($rows['actions']) && class_exists($className)) {
            $rowItem = new $className([]);
            $rows['actions'] = $rowItem;
        }
    }

    /**
     * Load the column structure from the JSON
     *
     * @param array $columns
     * @param array $target
     */
    private static function getGroupsColumns($columns, &$target)
    {
        $groupClass = static::getNamespace() . 'GroupItem';
        $newGroupArray = [
            'children' => [],
            'name' => 'main',
            'tag' => 'group',
        ];

        foreach ($columns as $key => $item) {
            if ($item['tag'] === 'group') {
                $groupItem = new $groupClass($item);
                $target[$groupItem->name] = $groupItem;
            } else {
                $newGroupArray['children'][$key] = $item;
            }
        }

        /// is there are loose columns, then we put it on a new group
        if (!empty($newGroupArray['children'])) {
            $groupItem = new $groupClass($newGroupArray);
            $target[$groupItem->name] = $groupItem;
        }
    }

    /**
     * 
     * @param string $message
     * @param array  $context
     */
    private static function saveError($message, $context = [])
    {
        $i18n = new Translator();
        $minilog = new MiniLog();
        $minilog->critical($i18n->trans($message, $context));
    }

    /**
     * Turns an xml into an array.
     *
     * @param SimpleXMLElement $xml
     *
     * @return array
     */
    private static function xmlToArray($xml): array
    {
        $array = [
            'tag' => $xml->getName(),
            'children' => [],
        ];

        /// attributes
        foreach ($xml->attributes() as $name => $value) {
            $array[$name] = (string) $value;
        }

        /// childs
        foreach ($xml->children() as $tag => $child) {
            $childAttr = $child->attributes();
            $name = static::xmlToArrayAux($tag, $childAttr);
            if ('' === $name) {
                $array['children'][] = static::xmlToArray($child);
                continue;
            }

            $array['children'][$name] = static::xmlToArray($child);
        }

        /// text
        $text = (string) $xml;
        if ('' !== $text) {
            $array['text'] = $text;
        }

        return $array;
    }

    /**
     * 
     * @param string            $tag
     * @param SimpleXMLElement $attributes
     *
     * @return string
     */
    private static function xmlToArrayAux($tag, $attributes)
    {
        if (isset($attributes->name)) {
            return (string) $attributes->name;
        }

        if ($tag === 'row' && isset($attributes->type)) {
            return (string) $attributes->type;
        }

        return '';
    }
}
