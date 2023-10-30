<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\UIComponent;

class Button extends UIComponent
{
    public function render(): string
    {
        return '<button type="button" class="btn btn-primary mr-1">' . $this->name . '</button>';
    }
}