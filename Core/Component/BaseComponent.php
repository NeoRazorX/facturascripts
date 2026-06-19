<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Component;

use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;

/**
 * Clase base abstracta para todos los componentes de interfaz.
 *
 * Proporciona la API de construcción fluida, el motor de validación (reglas con
 * nombre y closures arbitrarios), el patrón resolver para extraer valores de la
 * petición o del modelo, y la cadena de renderizado (edición, celda, solo lectura).
 * Las clases concretas extienden esta e implementan inputHtml(), schema() y templateDir().
 *
 * Ejemplo rápido:
 *   ComponentText::make('email')
 *       ->setLabel('email')->setRequired()->addRule('email')
 *       ->addRule(fn($v, $lang, $c) => validacion($v) ? null : $lang->trans('clave'))
 *       ->setCols(6);
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class BaseComponent
{
    protected string $fieldname;
    protected string $label = '';
    protected string $description = '';
    protected string $icon = '';
    protected bool $required = false;
    protected string $readonly = 'false';
    protected string $cssClass = '';
    protected int $cols = 0;
    protected int $tabindex = -1;
    protected mixed $value = null;

    protected array $validationErrors = [];
    protected array $validationRules = [];
    protected array $customRules = [];
    /** @var callable|null */
    protected $resolver = null;

    public function __construct(string $fieldname)
    {
        $this->fieldname = $fieldname;
        $this->label = $fieldname;
        $this->addDefaultRules();
    }

    public static function make(string $fieldname): static
    {
        return new static($fieldname);
    }

    public function setLabel(string $label, array $params = []): static
    {
        $this->label = Tools::lang()->trans($label, $params);
        return $this;
    }

    public function setDescription(string $description, array $params = []): static
    {
        $this->description = Tools::lang()->trans($description, $params);
        return $this;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function setRequired(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function setReadOnly(bool $readonly = true): static
    {
        $this->readonly = $readonly ? 'true' : 'false';
        return $this;
    }

    public function setReadOnlyDynamic(): static
    {
        $this->readonly = 'dinamic';
        return $this;
    }

    public function setCols(int $cols): static
    {
        $this->cols = $cols;
        return $this;
    }

    public function setCssClass(string $class): static
    {
        $this->cssClass = $class;
        return $this;
    }

    public function setTabIndex(int $index): static
    {
        $this->tabindex = $index;
        return $this;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function setValidationErrors(array $errors): static
    {
        $this->validationErrors = $errors;
        return $this;
    }

    public function validationErrors(): array
    {
        return $this->validationErrors;
    }

    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    public function setResolver(callable $fn): static
    {
        $this->resolver = $fn;
        return $this;
    }

    /**
     * Extrae el valor del componente de la petición actual o del modelo.
     *
     * Orden de resolución: callable personalizado → cuerpo POST → propiedad del modelo → null.
     * El resolver personalizado (asignado con setResolver()) cortocircuita el resto y recibe
     * la Request completa y el modelo para aplicar cualquier lógica a medida.
     */
    public function resolve(Request $request, ?object $model = null): mixed
    {
        if ($this->resolver !== null) {
            return ($this->resolver)($request, $model);
        }

        return $request->request->get($this->fieldname)
            ?? ($model !== null && property_exists($model, $this->fieldname)
                ? $model->{$this->fieldname}
                : null);
    }

    /**
     * Añade una regla con nombre o un closure validador personalizado.
     *
     * Reglas con nombre: 'email', 'numeric', 'max:N', 'min:N', 'min_val:N', 'max_val:N'.
     * Firma del closure: fn(mixed $valor, Translator $lang, BaseComponent $comp): ?string
     * Devolver null para pasar, o un string con el mensaje de error para fallar.
     * Los closures no se incluyen en schema() al no ser serializables.
     */
    public function addRule(string|callable $rule, mixed ...$params): static
    {
        if (is_callable($rule)) {
            $this->customRules[] = $rule;
        } else {
            $this->validationRules[] = ['rule' => $rule, 'params' => $params];
        }
        return $this;
    }

    public function validate(mixed $value): array
    {
        $errors = [];

        if ($this->required && ($value === null || $value === '')) {
            $errors[] = Tools::lang()->trans('field-required', ['%field%' => $this->label]);
        }

        foreach ($this->validationRules as $item) {
            $error = $this->applyRule($item['rule'], $value, $item['params']);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        $lang = Tools::lang();
        foreach ($this->customRules as $fn) {
            $error = $fn($value, $lang, $this);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Despacha el componente según el contexto de renderizado o procesamiento.
     *
     * Valores de contexto aceptados: 'edit', 'cell', 'readonly', 'process', 'validate'.
     * Es un punto de entrada conveniente para renderizadores externos; el ciclo de vida
     * del controlador usa processRequest() y renderEdit() directamente.
     */
    public function handle(Request $request, string $context, ?object $model = null): mixed
    {
        $value = $this->resolve($request, $model);
        $this->value = $value;

        return match ($context) {
            'edit'     => $this->renderEdit($value),
            'cell'     => $this->renderCell($value),
            'readonly' => $this->renderReadOnly($value),
            'process'  => $this->processRequest($request, $model),
            'validate' => $this->validate($value),
            default    => throw new \Exception("Unknown context: {$context}"),
        };
    }

    public function renderEdit(mixed $value = null): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        $label = '<label class="mb-0 small fw-semibold">'
            . htmlspecialchars($this->label)
            . ($this->required ? ' <span class="text-danger">*</span>' : '')
            . '</label>';

        $desc = $this->description
            ? '<small class="form-text text-muted">' . htmlspecialchars($this->description) . '</small>'
            : '';

        $input = $this->inputHtml();

        if ($this->icon) {
            $input = '<div class="input-group">'
                . '<span class="input-group-text"><i class="' . $this->icon . ' fa-fw"></i></span>'
                . $input
                . $this->renderInlineErrors()
                . '</div>';
        } else {
            $input .= $this->renderInlineErrors();
        }

        return '<div class="mb-3">' . $label . $input . $desc . '</div>';
    }

    public function renderCell(mixed $value = null, string $align = 'left'): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        return '<td class="text-' . $align . '">'
            . htmlspecialchars($this->displayValue())
            . '</td>';
    }

    public function renderReadOnly(mixed $value = null): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        return '<div class="mb-3">'
            . '<label class="mb-0 small fw-semibold">' . htmlspecialchars($this->label) . '</label>'
            . '<p class="form-control-plaintext py-0">' . htmlspecialchars($this->displayValue()) . '</p>'
            . '</div>';
    }

    public function processRequest(Request $request, ?object $model = null): array
    {
        $value = $this->resolve($request, $model);
        $errors = $this->validate($value);

        if (empty($errors) && $model !== null) {
            $model->{$this->fieldname} = $value;
        }

        return ['success' => empty($errors), 'errors' => $errors, 'value' => $value];
    }

    abstract public function schema(): array;

    public function fieldname(): string
    {
        return $this->fieldname;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function cols(): int
    {
        return $this->cols;
    }

    public function cssClass(): string
    {
        return $this->cssClass;
    }

    public function tabindex(): int
    {
        return $this->tabindex;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function isReadOnly(): bool
    {
        if ($this->readonly === 'dinamic') {
            return !empty($this->value);
        }

        return $this->readonly === 'true';
    }

    abstract protected function templateDir(): string;

    abstract protected function inputHtml(): string;

    protected function addDefaultRules(): void
    {
    }

    protected function displayValue(): string
    {
        return $this->value === null ? '-' : (string) $this->value;
    }

    protected function renderInlineErrors(): string
    {
        $html = '';
        foreach ($this->validationErrors as $error) {
            $html .= '<div class="invalid-feedback d-block">' . htmlspecialchars($error) . '</div>';
        }
        return $html;
    }

    protected function inputCssClass(string ...$base): string
    {
        $classes = array_filter($base);
        if ($this->cssClass) {
            $classes[] = $this->cssClass;
        }
        if ($this->hasValidationErrors()) {
            $classes[] = 'is-invalid';
        }
        return implode(' ', $classes);
    }

    protected function inputExtraParams(): string
    {
        $params = $this->required ? ' required=""' : '';
        $params .= $this->isReadOnly() ? ' readonly=""' : '';
        $params .= $this->tabindex >= 0 ? ' tabindex="' . $this->tabindex . '"' : '';
        return $params;
    }

    protected function combineClasses(string ...$classes): string
    {
        return implode(' ', array_filter($classes));
    }

    protected function colorToClass(string $color, string $prefix): string
    {
        $allowed = [
            'danger', 'dark', 'info', 'light', 'primary', 'secondary', 'success', 'warning',
            'outline-danger', 'outline-dark', 'outline-info', 'outline-light',
            'outline-primary', 'outline-secondary', 'outline-success', 'outline-warning',
        ];

        return in_array($color, $allowed) ? $prefix . $color : '';
    }

    protected function applyRule(string $rule, mixed $value, array $params): ?string
    {
        $colonPos = strpos($rule, ':');
        $ruleName = $colonPos !== false ? substr($rule, 0, $colonPos) : $rule;
        $ruleParam = $colonPos !== false ? substr($rule, $colonPos + 1) : ($params[0] ?? '');

        if ($value === null || $value === '') {
            return null;
        }

        return match ($ruleName) {
            'max'     => mb_strlen((string) $value) > (int) $ruleParam
                ? Tools::lang()->trans('value-too-long', ['%field%' => $this->label, '%max%' => $ruleParam])
                : null,
            'min'     => mb_strlen((string) $value) < (int) $ruleParam
                ? Tools::lang()->trans('value-too-short', ['%field%' => $this->label, '%min%' => $ruleParam])
                : null,
            'numeric' => !is_numeric($value)
                ? Tools::lang()->trans('value-must-be-numeric', ['%field%' => $this->label])
                : null,
            'email'   => !filter_var($value, FILTER_VALIDATE_EMAIL)
                ? Tools::lang()->trans('invalid-email')
                : null,
            'min_val' => (float) $value < (float) $ruleParam
                ? Tools::lang()->trans('value-too-low', ['%field%' => $this->label, '%min%' => $ruleParam])
                : null,
            'max_val' => (float) $value > (float) $ruleParam
                ? Tools::lang()->trans('value-too-high', ['%field%' => $this->label, '%max%' => $ruleParam])
                : null,
            default   => null,
        };
    }
}
