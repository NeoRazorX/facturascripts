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
 * Modal Bootstrap con contenido componible (puede contener un UIForm completo).
 *
 * Se añade a la UIPage como cualquier otro componente. En el render inicial se
 * emite oculto; un handler lo muestra con $response->openModal($modal), que
 * re-renderiza su fragmento y ordena show al cliente. Cuelga siempre de la
 * página, nunca dentro de un form.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIModal extends UIContainer
{
    protected string $title = '';
    protected array $titleParams = [];
    protected string $icon = '';

    /** Tamaño Bootstrap del diálogo: '', 'sm', 'lg', 'xl'. */
    protected string $size = '';

    protected function defaultTemplate(): string
    {
        return 'UI/Modal.html.twig';
    }

    /** Clave i18n del título del modal. */
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

    public function size(string $size): static
    {
        $this->size = $size;
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

    public function dialogClass(): string
    {
        return $this->size === '' ? 'modal-dialog' : 'modal-dialog modal-' . $this->size;
    }
}
