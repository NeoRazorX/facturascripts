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
 * Agrupación puramente visual: una fila Bootstrap con título opcional.
 * No participa en el naming de los campos ni en el ciclo de eventos.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIGroup extends UIContainer
{
    protected string $title = '';
    protected array $titleParams = [];

    /** Alinea los componentes al fondo de la fila (útil para mezclar inputs y checkboxes). */
    protected bool $alignBottom = false;

    protected function defaultTemplate(): string
    {
        return 'UI/Group.html.twig';
    }

    /** Clave i18n del título del grupo (separador visual). */
    public function title(string $key, array $params = []): static
    {
        $this->title = $key;
        $this->titleParams = $params;
        return $this;
    }

    public function titleText(): string
    {
        return $this->title === '' ? '' : Tools::lang()->trans($this->title, $this->titleParams);
    }

    public function alignBottom(bool $align = true): static
    {
        $this->alignBottom = $align;
        return $this;
    }

    public function isAlignBottom(): bool
    {
        return $this->alignBottom;
    }
}
