<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\PageOption;
use FacturaScripts\Core\Tools;
use SimpleXMLElement;

/**
 * Description of VisualItemLoadEngine
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
class VisualItemLoadEngine
{
    /**
     * Atributos de columna que el usuario puede personalizar
     *
     * @var array
     */
    private static array $customizableColumnKeys = ['display', 'level', 'numcolumns', 'order', 'title'];

    /**
     * Atributos del widget que el usuario puede personalizar
     *
     * @var array
     */
    private static array $customizableWidgetKeys = ['decimal', 'readonly'];

    /** @var string */
    private static string $namespace = '\\FacturaScripts\\Dinamic\\Lib\\Widget\\';

    public static function getNamespace(): string
    {
        return self::$namespace;
    }

    public static function setNamespace(string $namespace): void
    {
        self::$namespace = $namespace;
    }

    /**
     * Loads an xmlview data into a PageOption model.
     *
     * @param string $name
     * @param PageOption $model
     *
     * @return bool
     */
    public static function installXML(string $name, PageOption &$model): bool
    {
        $model->name = htmlspecialchars($name);

        $fileName = FS_FOLDER . '/Dinamic/XMLView/' . $model->name . '.xml';
        if (FS_DEBUG && !file_exists($fileName)) {
            $fileName = FS_FOLDER . '/Core/XMLView/' . $model->name . '.xml';
        }

        if (!file_exists($fileName)) {
            Tools::log()->error('error-processing-xmlview', ['%fileName%' => 'XMLView\\' . $model->name . '.xml']);
            return false;
        }

        $xml = simplexml_load_string(file_get_contents($fileName));
        if ($xml === false) {
            Tools::log()->error('error-processing-xmlview', ['%fileName%' => 'XMLView\\' . $model->name . '.xml']);
            return false;
        }

        // turns xml into an array
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
     * @param array $columns
     * @param array $modals
     * @param array $rows
     * @param PageOption $model
     */
    public static function loadArray(array &$columns, array &$modals, array &$rows, PageOption $model): void
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

        // we always need a row type actions
        $className = static::getNamespace() . 'RowActions';
        if (!isset($rows['actions']) && class_exists($className)) {
            $rowItem = new $className([]);
            $rows['actions'] = $rowItem;
        }
    }

    /**
     * Sobrescribe los atributos personalizados por el usuario.
     * Las columnas que ya no existen en el XML se ignoran (no se añaden).
     *
     * @param PageOption $base   Estructura cargada desde el XML (se modifica).
     * @param PageOption $custom Personalización guardada en base de datos.
     */
    public static function mergeCustomization(PageOption $base, PageOption $custom): void
    {
        $overrides = [];
        foreach ($custom->columns as $group) {
            if (($group['tag'] ?? '') === 'column') {
                $overrides[$group['name']] = $group;
                continue;
            }
            foreach ($group['children'] ?? [] as $col) {
                if (($col['tag'] ?? '') === 'column' && isset($col['name'])) {
                    $overrides[$col['name']] = $col;
                }
            }
        }

        if (empty($overrides)) {
            return;
        }

        // aplicamos los overrides recorriendo la estructura del XML.
        // ojo: el foreach por referencia debe iterar sobre una variable real, no
        // sobre una expresión temporal (p.ej. "$group['children'] ?? []"), o las
        // modificaciones se pierden y no afectarían a las columnas dentro de grupos
        foreach ($base->columns as &$group) {
            if (($group['tag'] ?? '') === 'column') {
                static::applyColumnOverride($group, $overrides);
                continue;
            }
            if (false === isset($group['children'])) {
                continue;
            }
            foreach ($group['children'] as &$col) {
                if (($col['tag'] ?? '') === 'column') {
                    static::applyColumnOverride($col, $overrides);
                }
            }
            unset($col);
        }
        unset($group);
    }

    /**
     * Aplica sobre una columna del XML los atributos personalizados guardados
     *
     * @param array $column    Columna de la estructura del XML (se modifica).
     * @param array $overrides Mapa nombre de columna -> definición guardada.
     */
    private static function applyColumnOverride(array &$column, array $overrides): void
    {
        $name = $column['name'] ?? null;
        if (null === $name || false === isset($overrides[$name])) {
            return;
        }

        $saved = $overrides[$name];
        foreach (self::$customizableColumnKeys as $key) {
            if (isset($saved[$key])) {
                $column[$key] = $saved[$key];
            }
        }

        if (false === isset($column['children'][0])) {
            return;
        }
        foreach (self::$customizableWidgetKeys as $key) {
            if (isset($saved['children'][0][$key])) {
                $column['children'][0][$key] = $saved['children'][0][$key];
            }
        }
    }

    /**
     * Load the column structure from the JSON
     *
     * @param array $columns
     * @param array $target
     */
    private static function getGroupsColumns(array $columns, array &$target): void
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

        // is there are loose columns, then we put it on a new group
        if (!empty($newGroupArray['children'])) {
            $groupItem = new $groupClass($newGroupArray);
            $target[$groupItem->name] = $groupItem;
        }
    }

    /**
     * Turns a xml into an array.
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

        // attributes
        foreach ($xml->attributes() as $name => $value) {
            $array[$name] = (string)$value;
        }

        // children
        foreach ($xml->children() as $tag => $child) {
            $childAttr = $child->attributes();
            $name = static::xmlToArrayAux($tag, $childAttr);
            if ('' === $name) {
                $array['children'][] = static::xmlToArray($child);
                continue;
            }

            $array['children'][$name] = static::xmlToArray($child);
        }

        // text
        $text = (string)$xml;
        if ('' !== $text) {
            $array['text'] = $text;
        }

        return $array;
    }

    /**
     * @param string $tag
     * @param SimpleXMLElement $attributes
     *
     * @return string
     */
    private static function xmlToArrayAux(string $tag, $attributes): string
    {
        if (isset($attributes->name)) {
            return (string)$attributes->name;
        }

        if ($tag === 'row' && isset($attributes->type)) {
            return (string)$attributes->type;
        }

        return '';
    }
}
