<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\UIComponents;

/**
 * Provides toolbar button and button-group support for UIListTab and UIListController.
 *
 * Button config keys:
 *   - action  (string)  Value set on input[name=action] when clicked.
 *   - icon    (string)  FontAwesome class for the icon.
 *   - label   (string)  Translation key for the button text.
 *   - confirm (bool)    If true, a confirm() dialog is shown before the action fires.
 *   - type    (string)  'modal' → opens a modal instead of submitting the form.
 *   - target  (string)  Modal element ID (required when type='modal').
 */
trait HasToolbarButtons
{
    /** @var array[] Groups: ['name', 'icon', 'label', 'buttons' => array[]] */
    private array $buttonGroups = [];

    /** @var array[] Standalone buttons outside any group */
    private array $actionButtons = [];

    /**
     * Registers a dropdown button group in the toolbar.
     * Returns $this for chaining; add buttons to the group with addGroupButton().
     */
    public function addButtonGroup(string $name, string $icon, string $labelKey): static
    {
        $this->buttonGroups[$name] = [
            'name'    => $name,
            'icon'    => $icon,
            'label'   => $labelKey,
            'buttons' => [],
        ];
        return $this;
    }

    /**
     * Adds a button to an existing group.
     * See class docblock for valid config keys.
     */
    public function addGroupButton(string $groupName, array $config): static
    {
        if (isset($this->buttonGroups[$groupName])) {
            $this->buttonGroups[$groupName]['buttons'][] = $config;
        }
        return $this;
    }

    /**
     * Adds a standalone button (outside any group) to the toolbar.
     * See class docblock for valid config keys.
     */
    public function addActionButton(array $config): static
    {
        $this->actionButtons[] = $config;
        return $this;
    }

    public function buttonGroups(): array
    {
        return $this->buttonGroups;
    }

    public function actionButtons(): array
    {
        return $this->actionButtons;
    }
}
