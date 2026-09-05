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
 * Botón de acción. Tres modos, excluyentes:
 *
 *  - UIButton::submit('save')       → <button type="submit"> del form contenedor;
 *    dispara '{form}:submit' (valida todo el form antes del handler).
 *  - UIButton::make('x')->action('duplicar') → dispara '{form}:duplicar' sin validar,
 *    serializando el form contenedor. Con ->pageAction() dispara 'page:duplicar'
 *    sin serializar ningún form.
 *  - UIButton::make('x')->link($url) → enlace puro.
 *
 * En HTML el evento viaja en el atributo name/value del botón (degradación sin
 * JS) y en data-ui-event (interceptado por UIEngine.js).
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIButton extends UIComponent
{
    protected string $label = '';
    protected array $labelParams = [];
    protected string $icon = '';
    protected string $color = 'primary';
    protected string $confirm = '';

    /** 'submit' | 'action' | 'link' */
    protected string $mode = 'action';

    protected string $event = '';
    protected string $url = '';

    /** true → el evento es de página (scope 'page'), no serializa ningún form. */
    protected bool $pageScope = false;

    protected function defaultTemplate(): string
    {
        return 'UI/Button.html.twig';
    }

    /** Crea un botón de submit del form contenedor. */
    public static function submit(string $name): static
    {
        $button = static::make($name);
        $button->mode = 'submit';
        $button->event = 'submit';
        $button->icon = 'fa-solid fa-floppy-disk';
        $button->label = 'save';
        return $button;
    }

    /** Clave i18n del texto del botón. */
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

    /** Variante Bootstrap: 'primary', 'outline-secondary', 'danger'… */
    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /** Mensaje de confirmación previo a disparar el evento. */
    public function confirm(string $message): static
    {
        $this->confirm = $message;
        return $this;
    }

    /** El botón dispara este evento del form contenedor (sin validar el form). */
    public function action(string $event): static
    {
        $this->mode = 'action';
        $this->event = $event;
        $this->pageScope = false;
        return $this;
    }

    /** El botón dispara un evento de página (registrado con $page->on()), sin serializar forms. */
    public function pageAction(string $event): static
    {
        $this->mode = 'action';
        $this->event = $event;
        $this->pageScope = true;
        return $this;
    }

    /** El botón es un enlace puro. */
    public function link(string $url): static
    {
        $this->mode = 'link';
        $this->url = $url;
        return $this;
    }

    // ------------------------------------------------------------------
    // Lectura para plantillas
    // ------------------------------------------------------------------

    public function mode(): string
    {
        return $this->mode;
    }

    public function labelText(): string
    {
        return $this->label === '' ? '' : Tools::lang()->trans($this->label, $this->labelParams);
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getConfirm(): string
    {
        return $this->confirm;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isPageScope(): bool
    {
        return $this->pageScope;
    }

    /** Identificador completo del evento ('{form}:{evento}' o 'page:{evento}'). */
    public function eventId(): string
    {
        if ($this->pageScope) {
            return UIPage::EVENT_SCOPE . ':' . $this->event;
        }

        $form = $this->form();
        if ($form === null) {
            throw new \LogicException(
                "Button '{$this->name}' fires form event '{$this->event}' but has no ancestor UIForm."
                . ' Use pageAction() for buttons outside forms.'
            );
        }
        return $form->eventId($this->event);
    }

    public function colClass(): string
    {
        return $this->cols <= 0 ? 'col-12 col-sm-auto' : parent::colClass();
    }
}
