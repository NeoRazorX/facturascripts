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

namespace FacturaScripts\Core\Lib\UI\Binding;

use FacturaScripts\Core\Lib\UI\UIForm;

/**
 * Mapea valores entre los campos de un UIForm y uno o varios modelos.
 *
 * fill(): modelos → campos (render inicial). apply(): campos → modelos (tras
 * validar, cuando el handler lo pide explícitamente). Nunca llama a save().
 *
 * Resolución del nombre de propiedad, por prioridad: bindTo() del campo,
 * entrada en $map del bind(), y por último el propio nombre del campo.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
final class ModelBinder
{
    /** @var array<array{model: object, map: array, only: ?array}> */
    private array $bindings = [];

    /**
     * @param object $model instancia del modelo
     * @param array $map ['campoForm' => 'propiedadModelo']
     * @param string[]|null $only limitar a estos campos del form; null = todos los que existan en el modelo
     */
    public function add(object $model, array $map = [], ?array $only = null): self
    {
        $this->bindings[] = ['model' => $model, 'map' => $map, 'only' => $only];
        return $this;
    }

    /** Copia los valores de los modelos a los campos del form. */
    public function fill(UIForm $form): void
    {
        foreach ($this->bindings as $binding) {
            foreach ($form->fields() as $field) {
                if (!$this->applies($binding, $field->name())) {
                    continue;
                }
                $property = $this->property($binding, $field);
                if (property_exists($binding['model'], $property)) {
                    $field->setValue($binding['model']->{$property});
                }
            }
        }
    }

    /** Escribe los valores actuales de los campos en los modelos. NO llama a save(). */
    public function apply(UIForm $form): void
    {
        foreach ($this->bindings as $binding) {
            foreach ($form->fields() as $field) {
                if (!$this->applies($binding, $field->name())) {
                    continue;
                }
                $property = $this->property($binding, $field);
                if (property_exists($binding['model'], $property)) {
                    $binding['model']->{$property} = $field->value();
                }
            }
        }
    }

    private function applies(array $binding, string $fieldName): bool
    {
        return $binding['only'] === null || in_array($fieldName, $binding['only'], true);
    }

    private function property(array $binding, $field): string
    {
        if ($field->bindProperty() !== $field->name()) {
            return $field->bindProperty();
        }
        return $binding['map'][$field->name()] ?? $field->name();
    }
}
