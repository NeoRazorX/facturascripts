<?php

namespace FacturaScripts\Core\Template;

abstract class SectionTab extends UIComponent
{
    /** @var string */
    public $counter = 0;

    /** @var string */
    public $icon;

    /** @var string */
    public $label;

    abstract public function jsInitFunction(): string;

    abstract public function jsRedrawFunction(): string;

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