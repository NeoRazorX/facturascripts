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

use FacturaScripts\Core\Lib\UI\Validation\RuleEngine;
use FacturaScripts\Core\Tools;

/**
 * Campo de formulario: componente con valor, reglas de validación y binding.
 *
 * El nombre lógico del campo es único dentro de su UIForm (dos forms distintos
 * pueden repetir nombre). El atributo name= HTML usa notación de corchetes
 * ('{form}[{campo}]'), que PHP parsea de forma nativa a array anidado, por lo
 * que el POST de un form nunca colisiona con el de otro.
 *
 * La etiqueta y la descripción se guardan como claves i18n y se traducen en el
 * momento del render, de modo que un fragmento re-renderizado es idéntico al
 * render inicial.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIField extends UIComponent
{
    protected mixed $value = null;

    protected string $label = '';
    protected array $labelParams = [];
    protected string $labelUrl = '';
    protected string $description = '';
    protected array $descriptionParams = [];
    protected string $icon = '';
    protected string $placeholder = '';
    protected string $cssClass = '';
    protected int $tabindex = -1;
    protected bool $required = false;

    /**
     * Controla el estado de solo lectura.
     * 'false'   → editable siempre.
     * 'true'    → siempre solo lectura.
     * 'dinamic' → solo lectura cuando el valor actual no está vacío.
     */
    protected string $readonly = 'false';

    /** Propiedad del modelo a la que se mapea este campo. Vacío = mismo nombre que el campo. */
    protected string $bindProperty = '';

    /** @var string[] errores de validación inyectados por UIForm::validate() */
    protected array $errors = [];

    protected RuleEngine $rules;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->label = $name;
        $this->rules = new RuleEngine();
    }

    // ------------------------------------------------------------------
    // Configuración fluida
    // ------------------------------------------------------------------

    /** Clave i18n de la etiqueta visible. Se traduce en el render. */
    public function label(string $key, array $params = []): static
    {
        $this->label = $key;
        $this->labelParams = $params;
        return $this;
    }

    /** URL para convertir la etiqueta en un enlace. */
    public function labelUrl(string $url): static
    {
        $this->labelUrl = $url;
        return $this;
    }

    /** Clave i18n del texto de ayuda bajo el campo. */
    public function description(string $key, array $params = []): static
    {
        $this->description = $key;
        $this->descriptionParams = $params;
        return $this;
    }

    /** Clase FontAwesome mostrada como prefijo del input. */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /** Marca el campo como obligatorio: la validación rechaza valores vacíos. */
    public function required(bool $required = true): static
    {
        $this->required = $required;
        $this->rules->setRequired($required);
        return $this;
    }

    /** Fuerza solo lectura independientemente del valor actual. */
    public function readOnly(bool $readonly = true): static
    {
        $this->readonly = $readonly ? 'true' : 'false';
        return $this;
    }

    /** Solo lectura si ya tiene valor (registro existente), editable si está vacío. */
    public function readOnlyDynamic(): static
    {
        $this->readonly = 'dinamic';
        return $this;
    }

    /** Clase CSS extra del elemento input. */
    public function cssClass(string $class): static
    {
        $this->cssClass = $class;
        return $this;
    }

    public function tabIndex(int $index): static
    {
        $this->tabindex = $index;
        return $this;
    }

    /**
     * Añade una regla de validación con nombre ('email', 'max:100'…) o un closure
     * fn(mixed $valor, Translator $lang): ?string.
     */
    public function rule(string|callable $rule, mixed ...$params): static
    {
        $this->rules->add($rule, ...$params);
        return $this;
    }

    /** Propiedad del modelo a la que se mapea este campo cuando difiere de su nombre. */
    public function bindTo(string $property): static
    {
        $this->bindProperty = $property;
        return $this;
    }

    // ------------------------------------------------------------------
    // Valor y ciclo de vida
    // ------------------------------------------------------------------

    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * Asigna el valor recibido del POST tras normalizarlo al tipo del campo.
     * El valor queda sticky: un re-render muestra lo que el usuario envió.
     */
    public function hydrate(mixed $raw): void
    {
        $this->value = $this->castFromRequest($raw);
    }

    /** Normaliza el valor crudo del POST al tipo del campo. Sobrescriben las subclases. */
    protected function castFromRequest(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        return is_scalar($raw) ? (string)$raw : $raw;
    }

    /** @return string[] mensajes de error; vacío si el valor actual es válido */
    public function validateValue(): array
    {
        return $this->rules->validate($this->value, $this->labelText());
    }

    /** Inyectados por UIForm::validate() para que el render muestre feedback en línea. */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    /** @return string[] */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    // ------------------------------------------------------------------
    // Lectura para plantillas
    // ------------------------------------------------------------------

    /** Nombre del atributo name= HTML: '{form}[{campo}]'. Requiere un form ancestro. */
    public function inputName(): string
    {
        $form = $this->form();
        if ($form === null) {
            throw new \LogicException(
                "Field '{$this->name}' has no ancestor UIForm; every field must live inside a form."
            );
        }
        return $form->name() . '[' . $this->name . ']';
    }

    /** Id del elemento input, para el atributo for= de la etiqueta. */
    public function inputId(): string
    {
        return $this->domId() . '-input';
    }

    public function labelText(): string
    {
        return Tools::lang()->trans($this->label, $this->labelParams);
    }

    public function getLabelUrl(): string
    {
        return $this->labelUrl;
    }

    public function descriptionText(): string
    {
        return $this->description === '' ? '' : Tools::lang()->trans($this->description, $this->descriptionParams);
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isReadOnly(): bool
    {
        if ($this->readonly === 'dinamic') {
            return !empty($this->value);
        }
        return $this->readonly === 'true';
    }

    public function getTabIndex(): int
    {
        return $this->tabindex;
    }

    /** Clases del elemento input: base del tipo + extra del usuario + is-invalid si hay errores. */
    public function inputCssClass(string $base = 'form-control'): string
    {
        $classes = [$base];
        if ($this->cssClass !== '') {
            $classes[] = $this->cssClass;
        }
        if ($this->hasErrors()) {
            $classes[] = 'is-invalid';
        }
        return implode(' ', $classes);
    }

    /** Propiedad del modelo asociada: bindTo() explícito o el propio nombre del campo. */
    public function bindProperty(): string
    {
        return $this->bindProperty !== '' ? $this->bindProperty : $this->name;
    }

    /** Valor actual como string para el atributo value= del input. */
    public function valueAttr(): string
    {
        return $this->value === null ? '' : (string)$this->value;
    }

    /** Representación textual legible del valor actual. */
    public function displayValue(): string
    {
        return $this->value === null ? '-' : (string)$this->value;
    }
}
