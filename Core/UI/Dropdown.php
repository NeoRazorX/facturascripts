<?php

namespace FacturaScripts\Core\UI;

class Dropdown extends Button
{
    protected $links = [];

    public function addLink(string $label, string $url, string $icon = ''): self
    {
        $this->links[] = ['icon' => $icon, 'label' => $label, 'url' => $url];

        return $this;
    }

    public function render(): string
    {
        $anchors = [];
        foreach ($this->links as $link) {
            if ($link['label'] === '-') {
                $anchors[] = '<div class="dropdown-divider"></div>';
                continue;
            }

            $icon = $link['icon'] ? '<i class="' . $link['icon'] . '"></i> ' : '';
            $anchors[] = '<a class="dropdown-item" href="' . $link['url'] . '">' . $icon . $link['label'] . '</a>';
        }

        $icon = $this->icon ? '<i class="' . $this->icon . '"></i> ' : '';
        $label = $this->label ?? $this->name;

        return '<div class="btn-group">'
            . '<div class="dropdown">'
            . '<button class="btn btn-' . $this->color . ' dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">'
            . $icon . $label
            . '</button>'
            . '<div class="dropdown-menu">' . implode("\n", $anchors) . '</div>'
            . '</div>'
            . '</div>';
    }
}