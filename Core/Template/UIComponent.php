<?php

namespace FacturaScripts\Core\Template;

abstract class UIComponent
{
    /** @var string */
    public $name;

    /** @var int */
    public $position = 0;

    abstract public function render(): string;

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}