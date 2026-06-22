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
 * Capa de presentación visual sobre BaseComponent.
 *
 * Añade todo lo relacionado con cómo se muestra un componente en la UI:
 *  - Configuración visual: etiqueta, columnas Bootstrap, icono, descripción,
 *    estado obligatorio, estado solo lectura y clase CSS extra.
 *  - Renderizado: renderEdit() para formularios, renderCell() para tablas y
 *    renderReadOnly() para vistas de detalle.
 *  - Helpers de renderizado: inputCssClass(), inputExtraParams(),
 *    renderInlineErrors() y displayValue().
 *
 * Las clases concretas (ComponentText, ComponentNumber, etc.) extienden esta
 * clase e implementan los tres métodos abstractos: inputHtml(), schema() y
 * templateDir().
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class FieldComponent extends BaseComponent
{
    protected string $label = '';
    protected string $labelUrl = '';
    protected string $description = '';
    protected string $icon = '';
    protected bool $required = false;

    /**
     * Controla el estado de solo lectura.
     * 'false'   → editable siempre.
     * 'true'    → siempre solo lectura.
     * 'dinamic' → solo lectura cuando el valor actual no está vacío.
     */
    protected string $readonly = 'false';

    /**
     * Visibilidad del campo en formularios y tablas.
     * 'none' → oculto (no se renderiza el input visible; en lista se salta la columna).
     * Cualquier otro valor → visible.
     */
    protected string $display = 'left';

    protected string $cssClass = '';
    protected int $cols = 0;
    protected int $tabindex = -1;
    protected string $cellAlign = 'start';

    public function __construct(string $fieldname)
    {
        parent::__construct($fieldname);
        $this->label = $fieldname;
    }
    /** Traduce la clave dada y la usa como etiqueta visible del campo. */
    public function setLabel(string $label, array $params = []): static
    {
        $this->label = Tools::lang()->trans($label, $params);
        return $this;
    }

    /** URL para convertir la etiqueta del campo en un enlace (<a href="...">label</a>). */
    public function setLabelUrl(string $url): static
    {
        $this->labelUrl = $url;
        return $this;
    }

    /** Traduce la clave dada y la muestra como texto de ayuda bajo el campo. */
    public function setDescription(string $description, array $params = []): static
    {
        $this->description = Tools::lang()->trans($description, $params);
        return $this;
    }

    /** Clase CSS de FontAwesome que se muestra como prefijo del input. */
    public function setIcon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /** Marca el campo como obligatorio: el motor de validación rechaza valores vacíos. */
    public function setRequired(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    /** Fuerza el campo a solo lectura independientemente del valor actual. */
    public function setReadOnly(bool $readonly = true): static
    {
        $this->readonly = $readonly ? 'true' : 'false';
        return $this;
    }

    /**
     * Modo dinámico: el campo es solo lectura si ya tiene un valor (registro existente)
     * y editable si está vacío (registro nuevo). Útil para claves primarias.
     */
    public function setReadOnlyDynamic(): static
    {
        $this->readonly = 'dinamic';
        return $this;
    }

    /** Anchura del campo en columnas Bootstrap (1-12). 0 oculta el campo en la cuadrícula. */
    public function setCols(int $cols): static
    {
        $this->cols = $cols;
        return $this;
    }

    /**
     * Establece la visibilidad del campo.
     * 'none' lo excluye del formulario (igual que display="none" en los XML antiguos).
     */
    public function setDisplay(string $display): static
    {
        $this->display = $display;
        return $this;
    }

    /** Devuelve true cuando el campo está marcado como invisible (display='none'). */
    public function isHidden(): bool
    {
        return $this->display === 'none';
    }

    /** Añade una clase CSS extra al elemento input, complementando las clases base. */
    public function setCssClass(string $class): static
    {
        $this->cssClass = $class;
        return $this;
    }

    /** Define el orden de tabulación con teclado. -1 usa el orden natural del DOM. */
    public function setTabIndex(int $index): static
    {
        $this->tabindex = $index;
        return $this;
    }

    /** Alineación de la celda en la tabla de listado: 'left'/'start', 'right'/'end' o 'center'. */
    public function setAlign(string $align): static
    {
        $this->cellAlign = match($align) {
            'left'  => 'start',
            'right' => 'end',
            default => $align,
        };
        return $this;
    }

    /** Devuelve la clase Bootstrap 5 de alineación (start, end, center). */
    public function align(): string
    {
        return $this->cellAlign;
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

    /**
     * Devuelve las clases Bootstrap de columna para el wrapper del campo en el formulario.
     *
     * Replica la lógica de ColumnItem::getColumnClasses() del sistema antiguo:
     *  - cols=0 → col-12 col-sm-6 col-md-4 col-xl  (adaptativo)
     *  - cols=12 → col-12
     *  - cols=N  → col-12 col-sm-6 col-md-4 col-xl-N
     *
     * ComponentCheckbox sobreescribe este método para devolver col-sm-auto cuando cols=0.
     */
    public function colClass(): string
    {
        if ($this->cols <= 0) {
            return 'col-12 col-sm-6 col-md-4 col-xl';
        }
        if ($this->cols === 12) {
            return 'col-12';
        }
        return 'col-12 col-md-' . $this->cols;
    }

    public function cssClass(): string
    {
        return $this->cssClass;
    }

    public function tabindex(): int
    {
        return $this->tabindex;
    }

    /**
     * Devuelve true si el campo está en modo solo lectura en este momento.
     *
     * En modo 'dinamic' depende de si el valor actual está vacío o no.
     */
    public function isReadOnly(): bool
    {
        if ($this->readonly === 'dinamic') {
            return !empty($this->value);
        }

        return $this->readonly === 'true';
    }
    /**
     * Renderiza el campo como input de formulario editable.
     *
     * Envuelve inputHtml() en la estructura Bootstrap estándar: etiqueta,
     * input-group con icono opcional, errores en línea y texto de ayuda.
     *
     * Renderiza el campo como un input oculto preservando el valor actual cuando
     * el componente tiene display='none'. La plantilla Twig llama a renderHidden()
     * en lugar de renderEdit() para los campos ocultos.
     */
    public function renderHidden(): string
    {
        return '<input type="hidden" name="' . $this->fieldname . '" value="' . htmlspecialchars((string) ($this->value ?? '')) . '">';
    }

    public function renderEdit(mixed $value = null): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        $labelText = htmlspecialchars($this->label);
        $labelInner = $this->labelUrl
            ? '<a href="' . htmlspecialchars($this->labelUrl) . '">' . $labelText . '</a>'
            : $labelText;
        $label = '<label class="mb-0">' . $labelInner . '</label>';

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

    /**
     * Renderiza el valor del componente como celda de tabla (<td>).
     *
     * Usa displayValue() para obtener una representación textual legible.
     * Las subclases pueden sobreescribir este método para renderizar HTML especial
     * (por ejemplo, ComponentCheckbox muestra un icono en lugar de texto).
     */
    public function renderCell(mixed $value = null): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        return '<td class="text-' . $this->cellAlign . '">'
            . htmlspecialchars($this->displayValue())
            . '</td>';
    }

    /**
     * Renderiza el campo en modo solo lectura para vistas de detalle.
     *
     * Muestra la etiqueta y el valor como texto plano, sin input HTML.
     */
    public function renderReadOnly(mixed $value = null): string
    {
        if ($value !== null) {
            $this->value = $value;
        }

        return '<div class="mb-3">'
            . '<label class="mb-0">' . htmlspecialchars($this->label) . '</label>'
            . '<p class="form-control-plaintext py-0">' . htmlspecialchars($this->displayValue()) . '</p>'
            . '</div>';
    }
    /**
     * Devuelve una representación estructurada del componente para APIs JSON o
     * para pasar configuración a JavaScript.
     */
    /** Representación en texto plano del valor actual. Usada por el sistema de exportación. */
    public function textValue(): string
    {
        return $this->displayValue();
    }

    /**
     * Despacha una acción de widget recibida por AJAX.
     *
     * Los componentes que necesiten responder a peticiones AJAX del tipo
     * `action=widget-*` deben sobreescribir este método y devolver el cuerpo
     * JSON de la respuesta. Devolver null indica que el componente no reconoce
     * la acción y el controlador responde con [].
     */
    public function handleWidgetAction(string $action, Request $request): ?string
    {
        return null;
    }

    /**
     * Registra los activos JS/CSS necesarios en AssetManager.
     *
     * Se invoca desde UIController antes de setTemplate() para que los scripts
     * queden registrados antes de que Twig evalúe assetManager.get('js') en el <head>.
     * La implementación base es un no-op; los componentes con JS propio (ComponentModalPicker…)
     * sobreescriben este método.
     */
    public function registerAssets(): void
    {
    }

    abstract public function schema(): array;

    /** Directorio de plantillas Twig del componente, relativo a Component/. */
    abstract protected function templateDir(): string;

    /** Devuelve el HTML del elemento input, sin envoltorio externo ni errores. */
    abstract protected function inputHtml(): string;
    /**
     * Representación textual del valor actual para renderCell() y renderReadOnly().
     *
     * Sobreescribe en la subclase para aplicar formato específico (número con
     * decimales, fecha localizada, etc.). La implementación base devuelve el valor
     * como string o '-' si es null.
     */
    protected function displayValue(): string
    {
        return $this->value === null ? '-' : (string) $this->value;
    }

    /**
     * Genera los divs invalid-feedback con los errores de validación del componente.
     *
     * Se inyecta dentro de renderEdit() tras el elemento input.
     */
    protected function renderInlineErrors(): string
    {
        $html = '';
        foreach ($this->validationErrors as $error) {
            $html .= '<div class="invalid-feedback d-block">' . htmlspecialchars($error) . '</div>';
        }
        return $html;
    }

    /**
     * Construye el atributo class del input combinando las clases base con la clase
     * extra del usuario y, si hay errores, 'is-invalid'.
     */
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

    /**
     * Genera los atributos HTML extra comunes: required, readonly y tabindex.
     *
     * Se añade directamente al elemento input mediante concatenación de cadenas.
     */
    protected function inputExtraParams(): string
    {
        $params = $this->required ? ' required=""' : '';
        $params .= $this->isReadOnly() ? ' readonly=""' : '';
        $params .= $this->tabindex >= 0 ? ' tabindex="' . $this->tabindex . '"' : '';
        return $params;
    }

    /** Sobreescribe isRequired() de BaseComponent con la propiedad configurable. */
    protected function isRequired(): bool
    {
        return $this->required;
    }
}
