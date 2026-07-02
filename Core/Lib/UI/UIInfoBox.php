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
 * Tarjeta informativa: icono + título + texto/valor, con color Bootstrap.
 * Re-renderizable como fragmento (útil para KPIs actualizados por eventos).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIInfoBox extends UIComponent
{
    protected string $title = '';
    protected array $titleParams = [];
    protected string $text = '';
    protected string $icon = '';
    protected string $color = 'primary';

    protected function defaultTemplate(): string
    {
        return 'UI/InfoBox.html.twig';
    }

    /** Clave i18n del título. */
    public function title(string $key, array $params = []): static
    {
        $this->title = $key;
        $this->titleParams = $params;
        return $this;
    }

    /** Texto o valor principal, ya formateado (no se traduce). */
    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function titleText(): string
    {
        return $this->title === '' ? '' : Tools::lang()->trans($this->title, $this->titleParams);
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
