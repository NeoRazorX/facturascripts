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

/**
 * Description of RowButton
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RowButton extends VisualItem
{

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $color;

    /**
     * @var bool
     */
    public $confirm;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var string
     */
    public $label;

    /**
     * Indicates the security level of the button
     *
     * @var int
     */
    public $level;

    /**
     * @var string
     */
    public $target;

    /**
     * @var string
     */
    public $type;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->action = $data['action'] ?? '';
        $this->color = $data['color'] ?? '';
        $this->confirm = isset($data['confirm']);
        $this->icon = $data['icon'] ?? '';
        $this->label = isset($data['label']) ? static::$i18n->trans($data['label']) : '';
        $this->level = isset($data['level']) ? (int)$data['level'] : 0;
        $this->target = $data['target'] ?? '';
        $this->type = $data['type'] ?? 'action';
    }

    public function render(bool $small = false, string $viewName = '', string $jsFunction = ''): string
    {
        if ($this->getLevel() < $this->level) {
            return '';
        }

        $cssClass = $small ? 'btn mr-1 ' : 'btn btn-sm mr-1 ';
        $cssClass .= empty($this->color) ? 'btn-light' : $this->colorToClass($this->color, 'btn-');
        $divID = empty($this->id) ? '' : ' id="' . $this->id . '"';

        $icon = empty($this->icon) ? '' : '<i class="' . $this->icon . ' fa-fw"></i> ';
        if ($small && empty($icon)) {
            $icon = $this->label;
        }

        $label = $this->label;
        if ($small) {
            $label = mb_strlen($label) < 8 ? '<span class="d-none d-xl-inline-block">' . $this->label . '</span>' :
                '<span class="d-none d-xl-inline-block">' . mb_substr($this->label, 0, 8) . '...</span>';
        }

        switch ($this->type) {
            case 'js':
                return '<button type="button"' . $divID . ' class="' . $cssClass . '" onclick="' . $this->action
                    . '" title="' . $this->label . '">' . $icon . $label . '</button>';

            case 'link':
                $target = empty($this->target) ? '' : ' target="' . $this->target . '"';
                return '<a ' . $target . $divID . ' class="' . $cssClass . '" href="' . $this->asset($this->action) . '"'
                    . ' title="' . $this->label . '">' . $icon . $label . '</a>';

            case 'modal':
                $modal = 'modal' . $this->action;
                return '<button type="button"' . $divID . ' class="' . $cssClass . '" data-toggle="modal" data-target="#'
                    . $modal . '" title="' . $this->label . '" onclick="setModalParentForm(\'' . $modal . '\', this.form)">'
                    . $icon . $label . '</button>';

            default:
                $onclick = $this->getOnClickValue($viewName, $jsFunction);
                return '<button type="button"' . $divID . ' class="' . $cssClass . '" onclick="' . $onclick
                    . '" title="' . $this->label . '">' . $icon . $label . '</button>';
        }
    }

    public function renderTop(): string
    {
        if ($this->getLevel() < $this->level) {
            return '';
        }

        $cssClass = 'btn btn-sm ';
        $cssClass .= empty($this->color) ? 'btn-secondary' : $this->colorToClass($this->color, 'btn-');
        $icon = empty($this->icon) ? '' : '<i class="' . $this->icon . ' fa-fw"></i> ';
        $divID = empty($this->id) ? '' : ' id="' . $this->id . '"';

        switch ($this->type) {
            case 'js':
                return '<button type="button"' . $divID . ' class="' . $cssClass . '" onclick="' . $this->action
                    . '" title="' . $this->label . '">' . $icon . $this->label . '</button> ';

            case 'link':
                $target = empty($this->target) ? '' : ' target="' . $this->target . '"';
                return '<a ' . $target . $divID . ' class="' . $cssClass . '" href="' . $this->asset($this->action) . '"'
                    . ' title="' . $this->label . '">' . $icon . $this->label . '</a> ';
        }

        return '';
    }

    /**
     * Fix url.
     *
     * @param string $url
     *
     * @return string
     */
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
                . $this->label . '\',\'' . self::$i18n->trans('are-you-sure-action') . '\',\''
                . self::$i18n->trans('cancel') . '\',\'' . self::$i18n->trans('confirm') . '\');';
        }

        if (empty($jsFunction)) {
            return 'this.form.action.value=\'' . $this->action . '\';this.form.submit();';
        }

        return $jsFunction . '(\'' . $viewName . '\',\'' . $this->action . '\');';
    }
}
