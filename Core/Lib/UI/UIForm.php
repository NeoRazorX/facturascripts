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

use FacturaScripts\Core\Lib\UI\Binding\ModelBinder;
use FacturaScripts\Core\Lib\UI\Validation\ErrorBag;
use FacturaScripts\Core\Tools;

/**
 * Formulario: la unidad de submit, hidratación, validación y binding.
 *
 * Cada UIForm se renderiza como un <form> real e independiente. Su nombre debe
 * ser único a nivel de página (UIPage lo verifica), pero los nombres de sus
 * campos solo deben ser únicos dentro del propio form.
 *
 * Eventos: onSubmit() registra el handler del evento 'submit' (valida antes);
 * on() registra eventos adicionales disparados por botones o cambios de campo.
 * El identificador que viaja en _ui_event es '{formName}:{evento}'.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIForm extends UIContainer
{
    protected string $title = '';
    protected array $titleParams = [];
    protected string $icon = '';

    /** @var array<string, array{handler: callable, validate: bool}> */
    private array $handlers = [];

    /** @var callable[] checks cross-field: fn(UIForm $f, ErrorBag $e): void */
    private array $checks = [];

    private ?ModelBinder $binder = null;

    private ErrorBag $errorBag;

    /** true tras hydrate(): los campos contienen lo enviado por el usuario. */
    private bool $hydrated = false;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->errorBag = new ErrorBag();
    }

    protected function defaultTemplate(): string
    {
        return 'UI/Form.html.twig';
    }

    // ------------------------------------------------------------------
    // Configuración
    // ------------------------------------------------------------------

    /** Clave i18n del título visible del formulario (legend). */
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

    // ------------------------------------------------------------------
    // Campos por nombre lógico
    // ------------------------------------------------------------------

    /**
     * Devuelve el campo con ese nombre lógico, buscando recursivamente en los
     * contenedores visuales del form (grupos, etc.).
     */
    public function field(string $name): ?UIField
    {
        foreach ($this->fields() as $field) {
            if ($field->name() === $name) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Verifica que no haya dos campos con el mismo nombre lógico dentro del form.
     * UIPage::assertValid() la invoca tras buildUI().
     */
    public function assertUniqueFieldNames(): void
    {
        $seen = [];
        foreach ($this->fields() as $field) {
            $name = $field->name();
            if (isset($seen[$name])) {
                throw new \LogicException(
                    "Duplicate field name '{$name}' in form '{$this->name}'."
                );
            }
            $seen[$name] = true;
        }
    }

    public function value(string $name): mixed
    {
        return $this->field($name)?->value();
    }

    /** @return array<string, mixed> nombre lógico → valor actual */
    public function values(): array
    {
        $result = [];
        foreach ($this->fields() as $field) {
            $result[$field->name()] = $field->value();
        }
        return $result;
    }

    // ------------------------------------------------------------------
    // Ciclo POST: hydrate → validate
    // ------------------------------------------------------------------

    /**
     * Asigna a cada campo su valor del POST.
     *
     * @param array $data el sub-array del POST correspondiente a este form
     *                    ($request->request->getArray($form->name()))
     */
    public function hydrate(array $data): void
    {
        foreach ($this->fields() as $field) {
            if ($field->isReadOnly()) {
                continue; // los campos de solo lectura conservan el valor del servidor
            }
            if (array_key_exists($field->name(), $data)) {
                $field->hydrate($data[$field->name()]);
            }
        }
        $this->hydrated = true;
    }

    public function isHydrated(): bool
    {
        return $this->hydrated;
    }

    /**
     * Ejecuta las reglas de cada campo y después los checks cross-field.
     * Inyecta los errores en los campos para que el render muestre el feedback
     * en línea, y devuelve el ErrorBag completo.
     */
    public function validate(): ErrorBag
    {
        $this->errorBag = new ErrorBag();

        foreach ($this->fields() as $field) {
            $errors = $field->validateValue();
            if (!empty($errors)) {
                $this->errorBag->addMany($field->name(), $errors);
            }
        }

        foreach ($this->checks as $check) {
            $check($this, $this->errorBag);
        }

        foreach ($this->fields() as $field) {
            $field->setErrors($this->errorBag->get($field->name()));
        }

        return $this->errorBag;
    }

    /** Registra un check cross-field: fn(UIForm $form, ErrorBag $errors): void. */
    public function addCheck(callable $check): static
    {
        $this->checks[] = $check;
        return $this;
    }

    public function errorBag(): ErrorBag
    {
        return $this->errorBag;
    }

    // ------------------------------------------------------------------
    // Eventos
    // ------------------------------------------------------------------

    /**
     * Handler del submit del formulario. Antes de invocarlo se hidrata y valida
     * todo el form; si la validación falla el handler no se ejecuta y el form se
     * re-renderiza con los errores en línea.
     *
     * Firma del handler: fn(UIEvent $e, UIResponse $r): void|UIResponse
     */
    public function onSubmit(callable $handler): static
    {
        return $this->on('submit', $handler, true);
    }

    /**
     * Registra un evento adicional del form, disparado por botones con action()
     * o por campos con onChange(). Por defecto no valida el form antes de
     * ejecutar el handler.
     */
    public function on(string $event, callable $handler, bool $validate = false): static
    {
        $this->handlers[$event] = ['handler' => $handler, 'validate' => $validate];
        return $this;
    }

    /** @return array{handler: callable, validate: bool}|null */
    public function handler(string $event): ?array
    {
        return $this->handlers[$event] ?? null;
    }

    /** Identificador completo del evento tal y como viaja en _ui_event. */
    public function eventId(string $event): string
    {
        return $this->name . ':' . $event;
    }

    // ------------------------------------------------------------------
    // Binding con modelos
    // ------------------------------------------------------------------

    /**
     * Asocia un modelo al form. Acumulable: un form puede alimentarse de varios
     * modelos. La escritura al modelo (apply) es siempre explícita en el handler.
     *
     * @param object $model instancia del modelo
     * @param array $map ['campoForm' => 'propiedadModelo'] para nombres distintos
     * @param string[]|null $only limitar el binding a estos campos del form
     */
    public function bind(object $model, array $map = [], ?array $only = null): static
    {
        $this->binder()->add($model, $map, $only);
        return $this;
    }

    /** Copia los valores de los modelos vinculados a los campos (GET / render inicial). */
    public function fill(): void
    {
        $this->binder?->fill($this);
    }

    /** Escribe los valores actuales de los campos en los modelos vinculados. NO llama a save(). */
    public function apply(): void
    {
        $this->binder?->apply($this);
    }

    public function binder(): ModelBinder
    {
        if ($this->binder === null) {
            $this->binder = new ModelBinder();
        }
        return $this->binder;
    }
}
