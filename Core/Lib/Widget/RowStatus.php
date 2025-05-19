<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;

/**
 * Description of RowStatus
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class RowStatus extends VisualItem
{
    /** @var string */
    public $fieldname;

    /** @var array */
    public $options;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->fieldname = empty($data['fieldname']) ? '' : $data['fieldname'];
        $this->options = $data['children'] ?? [];
    }

    public function legend(): string
    {
        $trs = '';
        foreach ($this->options as $opt) {
            if (!empty($opt['title'])) {
                $trs .= '<tr class="' . $this->colorToClass($opt['color'], 'table-') . '">'
                    . '<td class="text-center">' . Tools::lang()->trans($opt['title']) . '</td>'
                    . '</tr>';
            }
        }

        return empty($trs) ? '' : '<table class="table mb-0">' . $trs . '</table>';
    }

    /**
     * @param object $model
     * @param string $classPrefix
     *
     * @return string
     */
    public function trClass($model, string $classPrefix = 'table-'): string
    {
        foreach ($this->options as $opt) {
            $fieldName = $opt['fieldname'] ?? $this->fieldname;
            $value = $model->{$fieldName} ?? null;
            $this->replaceFieldValue($opt, $model);

            $rowColor = $this->getColorFromOption($opt, $value, $classPrefix);
            if (!empty($rowColor)) {
                return $rowColor;
            }
        }

        return '';
    }

    /**
     * @param object $model
     *
     * @return string
     */
    public function trTitle($model): string
    {
        foreach ($this->options as $opt) {
            $fieldName = $opt['fieldname'] ?? $this->fieldname;
            $value = $model->{$fieldName} ?? null;
            $this->replaceFieldValue($opt, $model);

            if ($this->applyOperatorFromOption($opt, $value)) {
                return isset($opt['title']) ? Tools::lang()->trans($opt['title']) : '';
            }
        }

        return '';
    }

    protected function replaceFieldValue(array &$opt, $model): void
    {
        if (false === array_key_exists('text', $opt)) {
            return;
        }

        // si el texto contiene field:XXX, lo reemplazamos por el valor de $model->XXX
        $matches = [];
        if (preg_match_all('/field:([a-zA-Z0-9_]+)/', $opt['text'], $matches)) {
            foreach ($matches[1] as $match) {
                $opt['text'] = str_replace('field:' . $match, $model->{$match} ?? '', $opt['text']);
            }
        }
    }
}
