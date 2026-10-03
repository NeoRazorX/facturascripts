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
 * Raíz del árbol de componentes de una página.
 *
 * Contiene forms, cards, tabs y modales de primer nivel. Su path() es vacío,
 * de modo que los paths de sus hijos empiezan por su propio nombre.
 *
 * También registra los handlers de eventos de página (sin form asociado),
 * cuyo identificador en _ui_event es 'page:{evento}'.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIPage extends UIContainer
{
    /** Nombre reservado del scope de eventos sin form. */
    public const EVENT_SCOPE = 'page';

    /** @var array<string, callable> */
    private array $handlers = [];

    public function __construct(string $name = 'page')
    {
        parent::__construct($name);
    }

    protected function defaultTemplate(): string
    {
        return 'UI/Page.html.twig';
    }

    /** La raíz no aporta segmento al path de sus descendientes. */
    public function path(): string
    {
        return '';
    }

    public function domId(): string
    {
        return 'ui-page';
    }

    /**
     * Registra un evento de página, sin form asociado (el JS no serializa
     * ningún formulario). Firma: fn(UIEvent $e, UIResponse $r): void|UIResponse
     */
    public function on(string $event, callable $handler): static
    {
        $this->handlers[$event] = $handler;
        return $this;
    }

    public function handler(string $event): ?callable
    {
        return $this->handlers[$event] ?? null;
    }

    public function eventId(string $event): string
    {
        return self::EVENT_SCOPE . ':' . $event;
    }

    /** Devuelve el form con ese nombre, buscando recursivamente en toda la página. */
    public function findForm(string $name): ?UIForm
    {
        foreach ($this->forms() as $form) {
            if ($form->name() === $name) {
                return $form;
            }
        }
        return null;
    }

    /**
     * Comprueba las invariantes del árbol tras buildUI(): nombres de form únicos
     * a nivel de página (no puede haber ambigüedad al enrutar eventos), el nombre
     * reservado 'page' sin usar, y nombres de campo únicos dentro de cada form.
     */
    public function assertValid(): void
    {
        $formNames = [];
        foreach ($this->forms() as $form) {
            $name = $form->name();
            if ($name === self::EVENT_SCOPE) {
                throw new \LogicException("Form name 'page' is reserved for page-level events.");
            }
            if (isset($formNames[$name])) {
                throw new \LogicException("Duplicate form name '{$name}' in page.");
            }
            $formNames[$name] = true;
            $form->assertUniqueFieldNames();
        }
    }
}
