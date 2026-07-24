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
 * Contrato mínimo de un componente de interfaz.
 *
 * Responsabilidades exclusivas de esta clase:
 *  - Identidad: expone el fieldname que lo identifica dentro de un formulario.
 *  - Valor: almacena y recupera el valor actual del componente.
 *  - Resolución: extrae el valor de la petición HTTP o del modelo, con soporte
 *    para un resolver personalizado que cortocircuita la lógica por defecto.
 *  - Validación: motor de reglas con nombre ('email', 'numeric', 'max:N'…) y
 *    closures arbitrarios con firma fn(mixed $valor, Translator $lang): ?string.
 *  - Procesamiento de petición: recibe el POST, valida y escribe en el modelo.
 *
 * Todo lo relacionado con presentación visual (etiqueta, columnas, icono,
 * renderizado HTML) vive en FieldComponent, que extiende esta clase.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class BaseComponent
{
    protected string $fieldname;
    protected mixed $value = null;

    protected array $validationErrors = [];
    protected array $validationRules = [];
    protected array $customRules = [];

    /** @var callable|null Resolver personalizado: fn(Request, ?object): mixed */
    protected $resolver = null;

    public function __construct(string $fieldname)
    {
        $this->fieldname = $fieldname;
        $this->addDefaultRules();
    }

    /** Crea una instancia con API fluida. */
    public static function make(string $fieldname): static
    {
        return new static($fieldname);
    }

    public function fieldname(): string
    {
        return $this->fieldname;
    }

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
     * Asigna un resolver personalizado.
     *
     * El callable recibe (Request, ?object) y devuelve el valor extraído.
     * Cuando está presente, sustituye completamente la lógica por defecto.
     */
    public function setResolver(callable $fn): static
    {
        $this->resolver = $fn;
        return $this;
    }

    /**
     * Extrae el valor del componente de la petición o del modelo.
     *
     * Orden: resolver personalizado → cuerpo POST → propiedad del modelo → null.
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
     * Añade una regla con nombre o un closure validador.
     *
     * Reglas con nombre disponibles: 'email', 'numeric', 'max:N', 'min:N',
     * 'min_val:N', 'max_val:N'.
     *
     * Firma del closure: fn(mixed $valor, Translator $lang): ?string
     * Devuelve null para pasar, o un string con el mensaje de error para fallar.
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

    /**
     * Ejecuta todas las reglas sobre el valor dado y devuelve los mensajes de error.
     *
     * Devuelve un array vacío si el valor es válido.
     */
    public function validate(mixed $value): array
    {
        $errors = [];
        $lang = Tools::lang();

        if ($this->isRequired() && ($value === null || $value === '')) {
            $errors[] = $lang->trans('field-required', ['%field%' => $this->fieldname]);
        }

        foreach ($this->validationRules as $item) {
            $error = $this->applyRule($item['rule'], $value, $item['params']);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        foreach ($this->customRules as $fn) {
            $error = $fn($value, $lang);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Inyecta los errores de validación desde el controlador al componente
     * para que el renderizador pueda mostrar el feedback en línea.
     */
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

    /**
     * Extrae el valor del POST, lo valida y lo escribe en el modelo si no hay errores.
     *
     * Devuelve ['success' => bool, 'errors' => string[], 'value' => mixed].
     */
    public function processRequest(Request $request, ?object $model = null): array
    {
        $value = $this->resolve($request, $model);
        $errors = $this->validate($value);

        if (empty($errors) && $model !== null) {
            $model->{$this->fieldname} = $value;
        }

        return ['success' => empty($errors), 'errors' => $errors, 'value' => $value];
    }

    /** Sobreescribe para registrar reglas por defecto al construir el componente. */
    protected function addDefaultRules(): void
    {
    }

    /**
     * Indica si el campo es obligatorio.
     *
     * La implementación base devuelve false. FieldComponent sobreescribe este
     * método con la propiedad $required que el usuario configura con setRequired().
     */
    protected function isRequired(): bool
    {
        return false;
    }
    
    private function applyRule(string $rule, mixed $value, array $params): ?string
    {
        $colonPos = strpos($rule, ':');
        $ruleName = $colonPos !== false ? substr($rule, 0, $colonPos) : $rule;
        $ruleParam = $colonPos !== false ? substr($rule, $colonPos + 1) : ($params[0] ?? '');

        if ($value === null || $value === '') {
            return null;
        }

        return match ($ruleName) {
            'max'     => mb_strlen((string) $value) > (int) $ruleParam
                ? Tools::lang()->trans('value-too-long', ['%field%' => $this->fieldname, '%max%' => $ruleParam])
                : null,
            'min'     => mb_strlen((string) $value) < (int) $ruleParam
                ? Tools::lang()->trans('value-too-short', ['%field%' => $this->fieldname, '%min%' => $ruleParam])
                : null,
            'numeric' => !is_numeric($value)
                ? Tools::lang()->trans('value-must-be-numeric', ['%field%' => $this->fieldname])
                : null,
            'email'   => !filter_var($value, FILTER_VALIDATE_EMAIL)
                ? Tools::lang()->trans('invalid-email')
                : null,
            'min_val' => (float) $value < (float) $ruleParam
                ? Tools::lang()->trans('value-too-low', ['%field%' => $this->fieldname, '%min%' => $ruleParam])
                : null,
            'max_val' => (float) $value > (float) $ruleParam
                ? Tools::lang()->trans('value-too-high', ['%field%' => $this->fieldname, '%max%' => $ruleParam])
                : null,
            default   => null,
        };
    }
}
