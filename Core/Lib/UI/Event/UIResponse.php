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

namespace FacturaScripts\Core\Lib\UI\Event;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Lib\UI\UIComponent;
use FacturaScripts\Core\Lib\UI\UIPage;
use FacturaScripts\Core\Lib\UI\Validation\ErrorBag;
use FacturaScripts\Core\Tools;

/**
 * Respuesta que construye un handler de evento.
 *
 * Se serializa al envelope JSON del protocolo HTML-over-the-wire:
 *   { protocol, ok, fragments: [{id, html, mode}], errors: {campo: [msgs]},
 *     notices: [{level, message}], actions: [{type, ...}] }
 *
 * Orden de aplicación en el cliente: redirect → fragments → errors → notices → actions.
 *
 * En peticiones sin JS el controlador usa redirectUrl() y hace render completo
 * de la página en su lugar.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
final class UIResponse
{
    public const PROTOCOL_VERSION = 1;

    private bool $ok = true;

    /** @var array<UIComponent|string> componentes o paths a re-renderizar */
    private array $rerenderTargets = [];

    /** @var array<string, string[]> '{form}.{campo}' → mensajes */
    private array $errors = [];

    /** @var array<array{type: string}> acciones declarativas para el cliente */
    private array $actions = [];

    private string $redirectUrl = '';

    public static function make(): self
    {
        return new self();
    }

    public function setOk(bool $ok): self
    {
        $this->ok = $ok;
        return $this;
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    /** Re-renderiza estos componentes (o paths) y los intercambia en el DOM. */
    public function rerender(UIComponent|string ...$targets): self
    {
        foreach ($targets as $target) {
            $this->rerenderTargets[] = $target;
        }
        return $this;
    }

    /** Mensaje informativo. Se registra en el log para que el render completo también lo muestre. */
    public function notice(string $message, array $params = []): self
    {
        Tools::log()->notice($message, $params);
        return $this;
    }

    public function warning(string $message, array $params = []): self
    {
        Tools::log()->warning($message, $params);
        return $this;
    }

    public function error(string $message, array $params = []): self
    {
        Tools::log()->error($message, $params);
        $this->ok = false;
        return $this;
    }

    /** Añade los errores de validación de un form al mapa del envelope. */
    public function fieldErrors(ErrorBag $errors, string $formName): self
    {
        foreach ($errors->all() as $field => $messages) {
            $this->errors[$formName . '.' . $field] = $messages;
        }
        if (!$errors->isEmpty()) {
            $this->ok = false;
        }
        return $this;
    }

    public function redirect(string $url): self
    {
        $this->redirectUrl = $url;
        return $this;
    }

    public function redirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /** Recarga la página completa en el cliente. */
    public function reload(): self
    {
        return $this->action('reload');
    }

    /** Pone el foco en un campo tras aplicar los fragmentos. */
    public function focus(UIComponent|string $target): self
    {
        return $this->action('focus', ['target' => $this->targetId($target)]);
    }

    /** Activa una pestaña de un UITabs. */
    public function activateTab(UIComponent|string $target): self
    {
        return $this->action('tab', ['target' => $this->targetId($target)]);
    }

    /** Hace scroll hasta un componente. */
    public function scrollTo(UIComponent|string $target): self
    {
        return $this->action('scroll', ['target' => $this->targetId($target)]);
    }

    /** Renderiza el UIModal indicado (fragmento) y ordena mostrarlo. */
    public function openModal(UIComponent|string $modal): self
    {
        $this->rerender($modal);
        return $this->action('modal', ['target' => $this->targetId($modal), 'action' => 'show']);
    }

    public function closeModal(UIComponent|string $modal): self
    {
        return $this->action('modal', ['target' => $this->targetId($modal), 'action' => 'hide']);
    }

    /** Añade una acción declarativa arbitraria del protocolo. */
    public function action(string $type, array $data = []): self
    {
        $this->actions[] = array_merge(['type' => $type], $data);
        return $this;
    }

    /**
     * Serializa la respuesta al envelope JSON, renderizando los fragmentos
     * contra el árbol actual de la página.
     */
    public function toEnvelope(UIPage $page): array
    {
        $fragments = [];
        foreach ($this->rerenderTargets as $target) {
            $component = is_string($target) ? $page->find($target) : $target;
            if ($component === null) {
                Tools::log()->warning('ui-fragment-not-found', ['%fragment%' => (string)$target]);
                continue;
            }
            $fragments[] = [
                'id' => $component->domId(),
                'html' => $component->render(),
                'mode' => 'replace',
            ];
        }

        $notices = [];
        $levels = ['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        foreach (MiniLog::read('', $levels) as $entry) {
            $notices[] = ['level' => $entry['level'], 'message' => $entry['message']];
        }

        if ($this->redirectUrl !== '') {
            array_unshift($this->actions, ['type' => 'redirect', 'url' => $this->redirectUrl]);
        }

        return [
            'protocol' => self::PROTOCOL_VERSION,
            'ok' => $this->ok,
            'fragments' => $fragments,
            'errors' => $this->errors,
            'notices' => $notices,
            'actions' => $this->actions,
        ];
    }

    private function targetId(UIComponent|string $target): string
    {
        return $target instanceof UIComponent ? $target->domId() : $target;
    }
}
