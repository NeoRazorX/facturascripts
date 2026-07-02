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
 * Bloque de HTML libre dentro del árbol de componentes. Acepta un string o un
 * callable (evaluado en cada render, útil para fragmentos dinámicos).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIHtml extends UIComponent
{
    /** @var string|callable */
    protected $content = '';

    protected function defaultTemplate(): string
    {
        return 'UI/Html.html.twig';
    }

    public function content(string|callable $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function contentHtml(): string
    {
        return is_callable($this->content) ? (string)($this->content)() : $this->content;
    }
}
