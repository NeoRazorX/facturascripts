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

/**
 * Panel de pestañas Bootstrap. Solo admite UITab como hijos; cada pestaña es un
 * contenedor libre (forms, grupos, campos…). La pestaña activa se persiste en
 * sessionStorage (behavior 'tab-persist' de UIEngine.js).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UITabs extends UIContainer
{
    protected function defaultTemplate(): string
    {
        return 'UI/Tabs.html.twig';
    }

    /** Crea una pestaña, la añade al panel y la devuelve para seguir componiendo. */
    public function tab(string $name, string $labelKey = '', string $icon = ''): UITab
    {
        $tab = UITab::make($name);
        if ($labelKey !== '') {
            $tab->label($labelKey);
        }
        if ($icon !== '') {
            $tab->icon($icon);
        }
        $this->add($tab);
        return $tab;
    }

    public function add(UIComponent ...$components): static
    {
        foreach ($components as $component) {
            if (!$component instanceof UITab) {
                throw new \LogicException(
                    "UITabs '{$this->name}' only accepts UITab children, got '" . $component->name() . "'."
                );
            }
        }
        return parent::add(...$components);
    }

    /** @return UITab[] */
    public function tabs(): array
    {
        return $this->children;
    }
}
