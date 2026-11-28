<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\UI;

use FacturaScripts\Core\Tools;

/**
 * Una pestaña de un UITabs. Contenedor libre: puede contener forms, grupos,
 * campos, botones… Su plantilla emite el tab-pane; la navegación la emite el
 * UITabs padre.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UITab extends UIContainer
{
    protected string $label = '';
    protected array $labelParams = [];
    protected string $icon = '';

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->label = $name;
    }

    protected function defaultTemplate(): string
    {
        return 'UI/Tab.html.twig';
    }

    /** Clave i18n del texto de la pestaña. */
    public function label(string $key, array $params = []): static
    {
        $this->label = $key;
        $this->labelParams = $params;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function labelText(): string
    {
        return Tools::lang()->trans($this->label, $this->labelParams);
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    /** true si es la primera pestaña de su panel (activa en el render inicial). */
    public function isFirst(): bool
    {
        if ($this->parent === null) {
            return false;
        }
        return array_key_first($this->parent->children()) === $this->name;
    }
}
