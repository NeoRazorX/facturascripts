<?php

namespace FacturaScripts\Core\Template;

abstract class SectionTab extends UIComponent
{
    /** @var string */
    public $icon;

    /** @var string */
    public $label;

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
}