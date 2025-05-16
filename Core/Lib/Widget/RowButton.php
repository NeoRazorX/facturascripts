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

namespace FacturaScripts\Core\Lib\Widget;

use FacturaScripts\Core\Tools;

/**
 * Description of RowButton
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowButton extends VisualItem
{
    /** @var string */
    public $action;

    /** @var string */
    public $color;

    /** @var bool */
    public $confirm;

    /** @var string */
    public $icon;

    /** @var string */
    public $label;

    /** @var int */
    public $level;

    /** @var string */
    public $target;

    /** @var string */
    public $title;

    /** @var string */
    public $type;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->action = $data['action'] ?? '';
        $this->color = $data['color'] ?? '';
        $this->confirm = isset($data['confirm']);
        $this->icon = $data['icon'] ?? '';
        $this->label = isset($data['label']) ? Tools::lang()->trans($data['label']) : '';
        $this->level = isset($data['level']) ? (int)$data['level'] : 0;
        $this->target = $data['target'] ?? '';
        $this->title = isset($data['title']) ? Tools::lang()->trans($data['title']) : '';
        $this->type = $data['type'] ?? 'action';
    }

    public function render(bool $small = false, string $viewName = '', string $jsFunction = ''): string
    {
        if ($this->getLevel() < $this->level) {
            return '';
        }

        if (empty($this->icon) && empty($this->label)) {
            $this->icon = 'far fa-question-circle';
        }

        $cssClass = $small ? 'btn me-1 ' : 'btn btn-sm me-1 ';
        $cssClass .= empty($this->color) ? 'btn-light' : $this->colorToClass($this->color, 'btn-');
        $divID = empty($this->id) ? '' : ' id="' . $this->id . '"';
        $title = empty($this->title) ? $this->label : $this->title;

        $icon = empty($this->icon) ? '' : '<i class="' . $this->icon . ' fa-fw"></i> ';
        if ($small && empty($icon)) {
            $icon = $this->label;
        }

        $label = $this->label;
        if ($small && $this->label) {
            $label = mb_strlen($this->label) < 8 ?
                '<span class="d-none d-xl-inline-block">' . $this->label . '</span>' :
                '<span class="d-none d-xl-inline-block">' . mb_substr($this->label, 0, 8) . '...</span>';
        }

        switch ($this->type) {
            case 'js':
                return '<button type="button"' . $divID . ' class="btn-spin-action ' . $cssClass . '" onclick="' . $this->action
                    . '" title="' . $title . '">' . $icon . $label . '</button>';

            case 'link':
                $target = empty($this->target) ? '' : ' target="' . $this->target . '"';
                return '<a ' . $target . $divID . ' class="btn-spin-action ' . $cssClass . '" href="' . $this->asset($this->action) . '"'
                    . ' title="' . $title . '">' . $icon . $label . '</a>';

            case 'modal':
                $modal = 'modal' . $this->action;
                return '<button type="button"' . $divID . ' class="btn-spin-action ' . $cssClass . '" data-bs-toggle="modal" data-bs-target="#'
                    . $modal . '" title="' . $title . '" onclick="setModalParentForm(\'' . $modal . '\', this.form)">'
                    . $icon . $label . '</button>';

            default:
                $onclick = $this->getOnClickValue($viewName, $jsFunction);
                return '<button type="button"' . $divID . ' class="btn-spin-action ' . $cssClass . '" onclick="' . $onclick
                    . '" title="' . $title . '">' . $icon . $label . '</button>';
        }
    }

    public function renderTop(): string
    {
        if ($this->getLevel() < $this->level) {
            return '';
        }

        if (empty($this->icon) && empty($this->label)) {
            $this->icon = 'far fa-question-circle';
        }

        $cssClass = 'btn btn-sm ';
        $cssClass .= empty($this->color) ? 'btn-secondary' : $this->colorToClass($this->color, 'btn-');
        $icon = empty($this->icon) ? '' : '<i class="' . $this->icon . ' fa-fw"></i>';
        $divID = empty($this->id) ? '' : ' id="' . $this->id . '"';
        $title = empty($this->title) ? $this->label : $this->title;

        $label = '';
        if ($this->label) {
            $label = ' ' . $this->label;
        }

        switch ($this->type) {
            case 'js':
                return '<button type="button"' . $divID . ' class="btn-spin-action ' . $cssClass . '" onclick="' . $this->action
                    . '" title="' . $title . '">' . $icon . $label . '</button> ';

            case 'link':
                $target = empty($this->target) ? '' : ' target="' . $this->target . '"';
                return '<a ' . $target . $divID . ' class="btn-spin-action ' . $cssClass . '" href="' . $this->asset($this->action) . '"'
                    . ' title="' . $title . '">' . $icon . $label . '</a> ';
        }

        return '';
    }

    protected function asset(string $url): string
    {
        $path = FS_ROUTE . '/';
        if (substr($url, 0, strlen($path)) == $path) {
            return $url;
        }

        // external link?
        $parts = explode(':', $url);
        if (in_array($parts[0], ['http', 'https'])) {
            return $url;
        }

        return str_replace('//', '/', $path . $url);
    }

    protected function getOnClickValue(string $viewName, string $jsFunction): string
    {
        if ($this->confirm) {
            return 'confirmAction(\'' . $viewName . '\',\'' . $this->action . '\',\''
                . $this->label . '\',\'' . Tools::lang()->trans('are-you-sure-action') . '\',\''
                . Tools::lang()->trans('cancel') . '\',\'' . Tools::lang()->trans('confirm') . '\');';
        }

        if (empty($jsFunction)) {
            $onsubmit = $this->action  === 'download' ? '' : 'this.form.onsubmit();';
            return 'this.form.action.value=\'' . $this->action . '\';' . $onsubmit . 'this.form.submit();';
        }

        return $jsFunction . '(\'' . $viewName . '\',\'' . $this->action . '\');';
    }
}
