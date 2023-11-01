<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UI;

use Exception;
use FacturaScripts\Core\Template\UI\SectionTab;
use FacturaScripts\Core\Template\UI\Component;

class Section extends Component
{
    /** @var Button[] */
    private $buttons = [];

    /** @var string */
    private $description;

    /** @var string */
    private $icon;

    /** @var SectionTab[] */
    private $tabs = [];

    /** @var string */
    private $title = '';

    public function addButton(string $name, ?Button $button = null): Button
    {
        // comprobamos que no exista ya un botón con ese nombre
        foreach ($this->buttons as $item) {
            if ($item->name() === $name) {
                throw new Exception('Button name already exists: ' . $name);
            }
        }

        if (null === $button) {
            $button = new Button($name);
        } else {
            $button->setName($name);
        }

        $button->setParent($this);
        $button->setPosition(count($this->buttons) * 10);

        $this->buttons[] = $button;
        $this->sortButtons();

        return $button;
    }

    public function addTab(string $name, SectionTab $tab): SectionTab
    {
        // comprobamos que no exista ya una pestaña con ese nombre
        foreach ($this->tabs as $item) {
            if ($item->name() === $name) {
                throw new Exception('Tab name already exists: ' . $name);
            }
        }

        $tab->setName($name);
        $tab->setParent($this);
        $tab->setPosition(count($this->tabs) * 10);

        $this->tabs[] = $tab;
        $this->sortTabs();

        return $tab;
    }

    public function button(string $name): ?Button
    {
        foreach ($this->buttons as $button) {
            if ($button->name() === $name) {
                return $button;
            }
        }

        return null;
    }

    public function buttons(): array
    {
        $this->sortButtons();

        return $this->buttons;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function render(string $context = ''): string
    {
        return '<div class="container-fluid" id="' . $this->id() . '">'
            . '<div class="form-row align-items-center">'
            . '<div class="col-sm">'
            . $this->renderButtons()
            . '</div>'
            . '<div class="col-sm-auto text-right">'
            . $this->renderTitle()
            . $this->renderDescription()
            . '</div>'
            . '</div>'
            . '</div>'
            . $this->renderTabs()
            . '<br>'
            . '<br>';
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function tab(string $name): ?SectionTab
    {
        foreach ($this->tabs as $tab) {
            if ($tab->name() === $name) {
                return $tab;
            }
        }

        return null;
    }

    public function tabs(): array
    {
        $this->sortTabs();

        return $this->tabs;
    }

    public function title(): string
    {
        return $this->title;
    }

    protected function removeButton(string $name): bool
    {
        foreach ($this->buttons as $key => $button) {
            if ($button->name() === $name) {
                unset($this->buttons[$key]);
                $this->sortButtons();
                return true;
            }
        }

        return false;
    }

    protected function removeTab(string $name): bool
    {
        foreach ($this->tabs as $key => $tab) {
            if ($tab->name() === $name) {
                unset($this->tabs[$key]);
                $this->sortTabs();
                return true;
            }
        }

        return false;
    }

    protected function renderButtons(): string
    {
        $html = '';
        foreach ($this->buttons() as $button) {
            $html .= $button->render();
        }

        return $html;
    }

    protected function renderDescription(): string
    {
        return '<p>' . $this->description . '</p>';
    }

    protected function renderTabs(): string
    {
        if (empty($this->tabs)) {
            return '';
        }

        $html = '';

        // inicializamos el javascript
        foreach ($this->tabs as $tab) {
            $jsCode = $tab->jsInitFunction();
            if (empty($jsCode)) {
                continue;
            }

            $html .= '<script>' . "\n"
                . $jsCode . "\n"
                . '</script>' . "\n";
        }

        // si solo hay una pestaña, solo mostramos su contenido
        if (count($this->tabs) === 1) {
            return $html . $this->tabs[0]->render();
        }

        // definimos primero las funciones de redibujado de las pestañas
        foreach ($this->tabs as $tab) {
            $jsCode = $tab->jsRedrawFunction();
            if (empty($jsCode)) {
                continue;
            }

            $html .= '<script>' . "\n"
                . 'function tab_' . $tab->id() . '_redraw() {' . "\n"
                . '    let tab = $("#' . $this->id() . ' a[href=\'#' . $tab->id() . '\']");' . "\n"
                . '    if (tab.length > 0) {' . "\n"
                . '        tab.tab("show");' . "\n"
                . '        ' . $jsCode . "\n"
                . '    }' . "\n"
                . '}' . "\n"
                . '</script>' . "\n";
        }

        $html .= '<ul class="nav nav-tabs mt-3" id="' . $this->id() . '">';

        foreach ($this->tabs() as $key => $tab) {
            $icon = empty($tab->icon) ? '' : '<i class="' . $tab->icon . ' mr-1"></i> ';
            $label = $tab->label ?? $tab->name();
            $counter = empty($tab->counter) ? '' : ' <span class="badge badge-secondary ml-1">' . $tab->counter . '</span>';
            $onclick = empty($tab->jsRedrawFunction()) ? '' : ' onclick="tab_' . $tab->id() . '_redraw();"';

            if ($key === 0) {
                $html .= '<li class="nav-item">'
                    . '<a class="nav-link active" href="#' . $tab->id() . '" data-toggle="tab"' . $onclick . '>'
                    . $icon . $label . $counter
                    . '</a>'
                    . '</li>';
                continue;
            }

            $html .= '<li class="nav-item">'
                . '<a class="nav-link" href="#' . $tab->id() . '" data-toggle="tab"' . $onclick . '>'
                . $icon . $label . $counter
                . '</a>'
                . '</li>';
        }

        $html .= '</ul>'
            . '<div class="tab-content">';

        foreach ($this->tabs() as $key => $tab) {
            $html .= $key === 0 ?
                '<div class="tab-pane active" id="' . $tab->id() . '">' . $tab->render() . '</div>' :
                '<div class="tab-pane" id="' . $tab->id() . '">' . $tab->render() . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function renderTitle(): string
    {
        if (empty($this->title)) {
            return '';
        }

        $icon = empty($this->icon) ? '' : '<i class="' . $this->icon . ' mr-1"></i> ';

        if ($this->position() === 0) {
            return '<h1 class="mb-0">' . $icon . $this->title . '</h1>';
        }

        if ($this->position() < 20) {
            return '<h2 class="mb-0">' . $icon . $this->title . '</h2>';
        }

        return '<h3 class="mb-0">' . $icon . $this->title . '</h3>';
    }

    private function sortButtons(): void
    {
        usort($this->buttons, function (Button $a, Button $b) {
            return $a->position() <=> $b->position();
        });
    }

    private function sortTabs(): void
    {
        usort($this->tabs, function (SectionTab $a, SectionTab $b) {
            return $a->position() <=> $b->position();
        });
    }
}