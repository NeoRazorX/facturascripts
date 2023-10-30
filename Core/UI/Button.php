<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\UIComponent;

class Button extends UIComponent
{
    public $color = 'secondary';

    public $label;

    public function render(): string
    {
        return '<button type="button" class="btn btn-' . $this->color . ' mr-1">'
            . ($this->label ?? $this->name)
            . '</button>';
    }
}