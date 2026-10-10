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

use FacturaScripts\Core\Html;

/**
 * Base de todo el árbol de componentes UI.
 *
 * Responsabilidades:
 *  - Identidad estructural: name (único dentro de su padre), path() jerárquico
 *    con puntos y domId() estable para el swap de fragmentos en el DOM.
 *  - Renderizado: render() delega en una plantilla Twig (resuelta contra
 *    Dinamic/View en producción, por lo que los plugins pueden sobrescribirla).
 *  - Registro de assets JS/CSS antes de que Twig evalúe el <head>.
 *
 * Cada plantilla de componente emite su propio wrapper con id="{{ c.domId() }}",
 * de modo que cualquier componente puede re-renderizarse aislado y el motor JS
 * puede sustituirlo por outerHTML.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIComponent
{
    protected string $name;
    protected ?UIContainer $parent = null;

    /** Anchura del wrapper en columnas Bootstrap (1-12). 0 = adaptativo. */
    protected int $cols = 0;

    protected bool $visible = true;

    /** Plantilla Twig alternativa; vacío usa defaultTemplate(). */
    protected string $template = '';

    public function __construct(string $name)
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException(
                "Invalid component name '{$name}': must start with a letter or underscore"
                . ' and contain only alphanumeric characters and underscores.'
            );
        }
        $this->name = $name;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    /** Plantilla Twig por defecto del componente, relativa a Core/View (p.ej. 'UI/Field/Text.html.twig'). */
    abstract protected function defaultTemplate(): string;

    public function name(): string
    {
        return $this->name;
    }

    public function setParent(?UIContainer $parent): void
    {
        $this->parent = $parent;
    }

    public function parent(): ?UIContainer
    {
        return $this->parent;
    }

    /**
     * Path estructural del componente: nombres de los ancestros unidos por puntos,
     * excluyendo la raíz UIPage. Determinista entre peticiones porque depende solo
     * de lo declarado en buildUI().
     */
    public function path(): string
    {
        $parentPath = $this->parent?->path() ?? '';
        return $parentPath === '' ? $this->name : $parentPath . '.' . $this->name;
    }

    /** Identidad estable del fragmento en el DOM, usada por el swap por outerHTML. */
    public function domId(): string
    {
        return 'ui-' . str_replace('.', '-', $this->path());
    }

    /** Form ancestro más cercano, o null si el componente vive fuera de un form. */
    public function form(): ?UIForm
    {
        for ($node = $this->parent; $node !== null; $node = $node->parent()) {
            if ($node instanceof UIForm) {
                return $node;
            }
        }
        return null;
    }

    /** Raíz del árbol, o null si el componente aún no está montado en una página. */
    public function page(): ?UIPage
    {
        $node = $this;
        while ($node->parent() !== null) {
            $node = $node->parent();
        }
        return $node instanceof UIPage ? $node : null;
    }

    /** Indica si este componente es o contiene un UIForm (los forms no pueden anidarse). */
    public function containsForm(): bool
    {
        return $this instanceof UIForm;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setCols(int $cols): static
    {
        $this->cols = $cols;
        return $this;
    }

    public function cols(): int
    {
        return $this->cols;
    }

    /**
     * Clases Bootstrap de columna para el wrapper del componente dentro de un row.
     *
     * cols=0 → adaptativo; cols=12 → ancho completo; cols=N → col-12 col-md-N.
     */
    public function colClass(): string
    {
        if ($this->cols <= 0) {
            return 'col-12 col-sm-6 col-md-4 col-xl';
        }
        if ($this->cols === 12) {
            return 'col-12';
        }
        return 'col-12 col-md-' . $this->cols;
    }

    /** Sustituye la plantilla Twig de este componente concreto. */
    public function setTemplate(string $template): static
    {
        $this->template = $template;
        return $this;
    }

    public function template(): string
    {
        return $this->template !== '' ? $this->template : $this->defaultTemplate();
    }

    /** Variables extra que la plantilla recibe además de 'c'. */
    protected function templateVars(): array
    {
        return [];
    }

    /** Renderiza el componente completo, wrapper incluido. Mismo código en render inicial y fragmentos. */
    public function render(): string
    {
        if (!$this->visible) {
            return '';
        }
        return Html::render($this->template(), array_merge(['c' => $this], $this->templateVars()));
    }

    /**
     * Registra los assets JS/CSS del componente en AssetManager.
     *
     * UIController lo invoca (recorriendo el árbol) antes de setTemplate() para
     * que los scripts queden registrados antes de que Twig evalúe el <head>.
     */
    public function registerAssets(): void
    {
    }
}
