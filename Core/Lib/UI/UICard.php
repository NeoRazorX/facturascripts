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
 * Tarjeta Bootstrap contenedora, con título e icono opcionales en la cabecera.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UICard extends UIContainer
{
    protected string $title = '';
    protected array $titleParams = [];
    protected string $icon = '';

    protected function defaultTemplate(): string
    {
        return 'UI/Card.html.twig';
    }

    /** Clave i18n del título de la cabecera. */
    public function title(string $key, array $params = []): static
    {
        $this->title = $key;
        $this->titleParams = $params;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function titleText(): string
    {
        return $this->title === '' ? '' : Tools::lang()->trans($this->title, $this->titleParams);
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
