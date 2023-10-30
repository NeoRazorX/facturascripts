<?php

namespace FacturaScripts\Core\UI;

class Dropdown extends Button
{
    protected $links = [];

    public function addLink(string $label, string $url): self
    {
        $this->links[] = ['label' => $label, 'url' => $url];
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

            $anchors[] = '<a class="dropdown-item" href="' . $link['url'] . '">' . $link['label'] . '</a>';
        }

        return '<div class="btn-group">'
            . '<div class="dropdown">'
            . '<button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">'
            . ($this->label ?? $this->name)
            . '</button>'
            . '<div class="dropdown-menu">' . implode("\n", $anchors) . '</div>'
            . '</div>'
            . '</div>';
    }
}