<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\UIComponent;

class Button extends UIComponent
{
    /** @var string */
    public $color = 'secondary';

    /** @var string */
    public $icon;

    /** @var string */
    public $label;

    public function render(): string
    {
        $icon = $this->icon ? '<i class="' . $this->icon . '"></i> ' : '';
        $label = $this->label ?? $this->name;

        return '<button type="button" class="btn btn-' . $this->color . ' mr-1">'
            . $icon . $label
            . '</button>';
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
}