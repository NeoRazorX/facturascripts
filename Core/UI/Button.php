<?php

namespace FacturaScripts\Core\UI;

use FacturaScripts\Core\Template\UIComponent;

class Button extends UIComponent
{
    /** @var string */
    public $color = 'secondary';

    /** @var int */
    public $counter = 0;

    /** @var string */
    public $description;

    /** @var string */
    public $icon;

    /** @var string */
    public $label;

    public function render(): string
    {
        $icon = $this->icon ? '<i class="' . $this->icon . ' mr-1"></i> ' : '';
        $label = $this->label ?? $this->name;
        $counter = empty($this->counter) ? '' : '<span class="badge badge-light ml-1">' . $this->counter . '</span> ';

        return '<button type="button" class="btn btn-' . $this->color . ' mr-1" title="' . $this->description . '">'
            . $icon . $label . $counter
            . '</button>';
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function setCounter(int $counter): self
    {
        $this->counter = $counter;

        return $this;
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

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
}