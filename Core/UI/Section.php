<?php

namespace FacturaScripts\Core\UI;

use Exception;
use FacturaScripts\Core\Template\SectionTab;
use FacturaScripts\Core\Template\UIComponent;

class Section extends UIComponent
{
    /** @var Button[] */
    private $buttons = [];

    /** @var SectionTab[] */
    private $tabs = [];

    /** @var string */
    private $title = '';

    public function addButton(string $name, ?Button $button = null, ?int $position = null): Button
    {
        if (null === $button) {
            $button = new Button();
        }

        $button->name = $name;
        $button->position = $position ?? count($this->buttons) * 10;

        $this->buttons[] = $button;
        $this->sortButtons();

        return $button;
    }

    public function addTab(string $name, SectionTab $tab, ?int $position = null): SectionTab
    {
        // comprobamos que no exista ya una pestaña con ese nombre
        foreach ($this->tabs as $item) {
            if ($item->name === $name) {
                throw new Exception('Tab name already exists: ' . $name);
            }
        }

        $tab->name = $name;
        $tab->position = $position ?? count($this->tabs) * 10;

        $this->tabs[] = $tab;
        $this->sortTabs();

        return $tab;
    }

    public function button(string $name): ?Button
    {
        foreach ($this->buttons as $button) {
            if ($button->name === $name) {
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

    public function render(): string
    {
        return '<div class="container-fluid p-2 border-top">'
            . '<div class="row">'
            . '<div class="col">'
            . '<h1 class="mb-0">' . $this->name . '</h1>'
            . '<p>' . $this->title . '</p>'
            . '</div>'
            . '</div>'
            . $this->renderButtons()
            . '</div>'
            . $this->renderTabs();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function tab(string $name): ?SectionTab
    {
        foreach ($this->tabs as $tab) {
            if ($tab->name === $name) {
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

    protected function renderButtons(): string
    {
        if (empty($this->buttons)) {
            return '';
        }

        $html = '<div class="row">'
            . '<div class="col">';

        foreach ($this->buttons() as $button) {
            $html .= $button->render();
        }

        $html .= '</div>'
            . '</div>';

        return $html;
    }

    public function renderTabs(): string
    {
        if (empty($this->tabs)) {
            return '';
        }

        if (count($this->tabs) === 1) {
            return $this->tabs[0]->render();
        }

        $html = '<ul class="nav nav-tabs">';

        foreach ($this->tabs() as $key => $tab) {
            if ($key === 0) {
                $html .= '<li class="nav-item">'
                    . '<a class="nav-link active" href="#' . $tab->name . '" data-toggle="tab">' . ($tab->label ?? $tab->name) . '</a>'
                    . '</li>';
                continue;
            }

            $html .= '<li class="nav-item">'
                . '<a class="nav-link" href="#' . $tab->name . '" data-toggle="tab">' . ($tab->label ?? $tab->name) . '</a>'
                . '</li>';
        }

        $html .= '</ul>'
            . '<div class="tab-content">';

        foreach ($this->tabs() as $key => $tab) {
            $html .= $key === 0 ?
                '<div class="tab-pane active" id="' . $tab->name . '">' . $tab->render() . '</div>' :
                '<div class="tab-pane" id="' . $tab->name . '">' . $tab->render() . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function sortButtons(): void
    {
        usort($this->buttons, function (Button $a, Button $b) {
            return $a->position <=> $b->position;
        });
    }

    private function sortTabs(): void
    {
        usort($this->tabs, function (SectionTab $a, SectionTab $b) {
            return $a->position <=> $b->position;
        });
    }
}