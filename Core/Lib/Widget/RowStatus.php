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

/**
 * Description of RowStatus
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class RowStatus extends VisualItem
{

    /**
     *
     * @var string
     */
    public $fieldname;

    /**
     *
     * @var array
     */
    public $options = [];

    /**
     *
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->fieldname = empty($data['fieldname']) ? '' : $data['fieldname'];
        $this->options = $data['children'];
    }

    /**
     *
     * @return string
     */
    public function legend(): string
    {
        $trs = '';
        foreach ($this->options as $opt) {
            $title = $opt['title'] ?? '?';
            $trs .= '<tr class="' . $this->colorToClass($opt['color'], 'table-') . '">'
                . '<td class="text-center">' . static::$i18n->trans($title) . '</td>'
                . '</tr>';
        }

        return empty($trs) ? '' : '<table class="table mb-0">' . $trs . '</table>';
    }

    /**
     *
     * @param object $model
     * @param string $classPrefix
     *
     * @return string
     */
    public function trClass($model, $classPrefix = 'table-')
    {
        foreach ($this->options as $opt) {
            $fieldname = isset($opt['fieldname']) ? $opt['fieldname'] : $this->fieldname;
            $value = isset($model->{$fieldname}) ? $model->{$fieldname} : null;
            $rowColor = $this->getColorFromOption($opt, $value, $classPrefix);
            if (!empty($rowColor)) {
                return $rowColor;
            }
        }

        return '';
    }

    /**
     *
     * @param object $model
     *
     * @return string
     */
    public function trTitle($model)
    {
        foreach ($this->options as $opt) {
            $fieldname = isset($opt['fieldname']) ? $opt['fieldname'] : $this->fieldname;
            $value = isset($model->{$fieldname}) ? $model->{$fieldname} : null;
            if ($this->applyOperatorFromOption($opt, $value)) {
                return isset($opt['title']) ? static::$i18n->trans($opt['title']) : '';
            }
        }

        return '';
    }
}
