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
 * Componente contenedor: mantiene hijos indexados por nombre y ofrece búsqueda
 * por path relativo. Los contenedores concretos (UIPage, UIForm, UIGroup,
 * UITabs…) solo aportan su plantilla y comportamiento propio.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIContainer extends UIComponent
{
    /** @var UIComponent[] keyed by name */
    protected array $children = [];

    /**
     * Añade uno o varios componentes hijos.
     *
     * Lanza LogicException si ya existe un hijo con el mismo nombre en ESTE
     * contenedor, o si se intenta anidar un UIForm dentro de otro (HTML no
     * permite formularios anidados).
     */
    public function add(UIComponent ...$components): static
    {
        foreach ($components as $component) {
            $name = $component->name();
            if (isset($this->children[$name])) {
                throw new \LogicException(
                    "Duplicate component name '{$name}' in container '{$this->name}'."
                );
            }

            if ($component->containsForm() && ($this instanceof UIForm || $this->form() !== null)) {
                throw new \LogicException(
                    "Cannot nest a UIForm ('{$name}') inside form '" . ($this->form() ?? $this)->name() . "'."
                );
            }

            $component->setParent($this);
            $this->children[$name] = $component;
        }
        return $this;
    }

    /** Devuelve el hijo directo con ese nombre, o null. */
    public function get(string $name): ?UIComponent
    {
        return $this->children[$name] ?? null;
    }

    /** Elimina el hijo directo con ese nombre. No lanza error si no existe. */
    public function remove(string $name): static
    {
        if (isset($this->children[$name])) {
            $this->children[$name]->setParent(null);
            unset($this->children[$name]);
        }
        return $this;
    }

    /**
     * Busca un componente descendiente por path relativo con puntos
     * (p.ej. 'general.identification.nombre').
     */
    public function find(string $path): ?UIComponent
    {
        $parts = explode('.', $path, 2);
        $child = $this->get($parts[0]);
        if ($child === null || count($parts) === 1) {
            return $child;
        }
        return $child instanceof UIContainer ? $child->find($parts[1]) : null;
    }

    /** @return UIComponent[] keyed by name */
    public function children(): array
    {
        return $this->children;
    }

    /** @return UIField[] todos los campos descendientes (recursivo), en orden de declaración */
    public function fields(): array
    {
        $result = [];
        foreach ($this->children as $child) {
            if ($child instanceof UIField) {
                $result[] = $child;
            } elseif ($child instanceof UIContainer) {
                $result = array_merge($result, $child->fields());
            }
        }
        return $result;
    }

    /** @return UIForm[] todos los forms descendientes (recursivo) */
    public function forms(): array
    {
        $result = [];
        foreach ($this->children as $child) {
            if ($child instanceof UIForm) {
                $result[] = $child;
            }
            if ($child instanceof UIContainer) {
                $result = array_merge($result, $child->forms());
            }
        }
        return $result;
    }

    /** Aplica el callable a este componente y a todos sus descendientes. */
    public function walk(callable $fn): void
    {
        $fn($this);
        foreach ($this->children as $child) {
            if ($child instanceof UIContainer) {
                $child->walk($fn);
            } else {
                $fn($child);
            }
        }
    }

    public function containsForm(): bool
    {
        if ($this instanceof UIForm) {
            return true;
        }
        foreach ($this->children as $child) {
            if ($child->containsForm()) {
                return true;
            }
        }
        return false;
    }

    public function registerAssets(): void
    {
        foreach ($this->children as $child) {
            $child->registerAssets();
        }
    }
}
