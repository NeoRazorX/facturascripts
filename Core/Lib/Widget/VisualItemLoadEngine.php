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

use FacturaScripts\Core\Model;

/**
 * Description of VisualItemLoadEngine
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class VisualItemLoadEngine
{

    /**
     * Add to the configuration of a controller
     *
     * @param string           $name
     * @param Model\PageOption $model
     *
     * @return boolean
     */
    public static function installXML($name, &$model)
    {
        $model->name = $name;

        $fileName = FS_FOLDER . '/Dinamic/XMLView/' . $name . '.xml';
        if (FS_DEBUG && !file_exists($fileName)) {
            $fileName = FS_FOLDER . '/Core/XMLView/' . $name . '.xml';
        }

        $xml = simplexml_load_string(file_get_contents($fileName));
        if ($xml === false) {
            return false;
        }

        /// turns xml into an array
        $array = static::xmlToArray($xml);
        $columns = [];
        $modals = [];
        $rows = [];
        foreach ($array['children'] as $value) {
            switch ($value['tag']) {
                case 'columns':
                    $columns = $value['children'];
                    break;

                case 'modals':
                    $modals = $value['children'];
                    break;

                case 'rows':
                    $rows = $value['children'];
                    break;
            }
        }

        self::loadArray($columns, $modals, $rows, $model);
        return true;
    }

    /**
     * Load the column structure from the JSON
     *
     * @param array            $columns
     * @param array            $modals
     * @param array            $rows
     * @param Model\PageOption $model
     */
    public static function loadArray($columns, $modals, $rows, &$model)
    {
        static::getGroupsColumns($columns, $model->columns);
        static::getGroupsColumns($modals, $model->modals);

        foreach ($rows as $item) {
            //$rowItem = RowItem::newFromJSON($item);
            //$model->rows[$rowItem->type] = $rowItem;
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
        $newGroupArray = [
            'name' => 'main',
            'children' => [],
        ];

        foreach ($columns as $item) {
            if ($item['tag'] === 'group') {
                $groupItem = new GroupItem($item);
                $target[$groupItem->name] = $groupItem;
            } else {
                $newGroupArray['children'][] = $item;
            }
        }

        /// is there are loose columns, then we put it on a new group
        if (!empty($newGroupArray['children'])) {
            $groupItem = new GroupItem($newGroupArray);
            $target[$groupItem->name] = $groupItem;
        }
    }

    /**
     * Turns an xml into an array.
     *
     * @param \SimpleXMLElement $xml
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
        if ('' !== trim($text)) {
            $array['text'] = $text;
        }

        return $array;
    }

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
