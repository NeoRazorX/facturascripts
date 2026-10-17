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

namespace FacturaScripts\Core\Lib\UI\Validation;

use FacturaScripts\Core\Tools;

/**
 * Motor de reglas de validación de un campo.
 *
 * Acepta reglas con nombre ('email', 'numeric', 'max:N', 'min:N', 'min_val:N',
 * 'max_val:N') y closures con firma fn(mixed $valor, Translator $lang): ?string
 * que devuelven null para pasar o el mensaje de error para fallar.
 *
 * Los mensajes usan la etiqueta visible del campo, no su nombre interno.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
final class RuleEngine
{
    /** @var array<array{rule: string, params: array}> */
    private array $namedRules = [];

    /** @var callable[] */
    private array $customRules = [];

    private bool $required = false;

    public function add(string|callable $rule, mixed ...$params): self
    {
        if (is_callable($rule)) {
            $this->customRules[] = $rule;
        } else {
            $this->namedRules[] = ['rule' => $rule, 'params' => $params];
        }
        return $this;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    /**
     * Ejecuta todas las reglas sobre el valor y devuelve los mensajes de error.
     *
     * @param mixed $value valor ya casteado por el campo
     * @param string $label etiqueta visible del campo, usada en los mensajes
     * @return string[] vacío si el valor es válido
     */
    public function validate(mixed $value, string $label): array
    {
        $errors = [];
        $lang = Tools::lang();

        if ($this->required && ($value === null || $value === '')) {
            $errors[] = $lang->trans('field-required', ['%field%' => $label]);
        }

        foreach ($this->namedRules as $item) {
            $error = $this->applyRule($item['rule'], $value, $item['params'], $label);
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

    private function applyRule(string $rule, mixed $value, array $params, string $label): ?string
    {
        $colonPos = strpos($rule, ':');
        $ruleName = $colonPos !== false ? substr($rule, 0, $colonPos) : $rule;
        $ruleParam = $colonPos !== false ? substr($rule, $colonPos + 1) : ($params[0] ?? '');

        // las reglas solo se aplican sobre valores presentes; required cubre los vacíos
        if ($value === null || $value === '') {
            return null;
        }

        return match ($ruleName) {
            'max'     => mb_strlen((string)$value) > (int)$ruleParam
                ? Tools::lang()->trans('value-too-long', ['%field%' => $label, '%max%' => $ruleParam])
                : null,
            'min'     => mb_strlen((string)$value) < (int)$ruleParam
                ? Tools::lang()->trans('value-too-short', ['%field%' => $label, '%min%' => $ruleParam])
                : null,
            'numeric' => !is_numeric($value)
                ? Tools::lang()->trans('value-must-be-numeric', ['%field%' => $label])
                : null,
            'email'   => !filter_var($value, FILTER_VALIDATE_EMAIL)
                ? Tools::lang()->trans('invalid-email')
                : null,
            'min_val' => (float)$value < (float)$ruleParam
                ? Tools::lang()->trans('value-too-low', ['%field%' => $label, '%min%' => $ruleParam])
                : null,
            'max_val' => (float)$value > (float)$ruleParam
                ? Tools::lang()->trans('value-too-high', ['%field%' => $label, '%max%' => $ruleParam])
                : null,
            default   => null,
        };
    }
}
