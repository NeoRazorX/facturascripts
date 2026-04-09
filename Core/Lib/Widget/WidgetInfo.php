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

namespace FacturaScripts\Core\Lib\Widget;

use Exception;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;

/**
 * Widget informativo para mostrar textos, alertas Bootstrap 5, iconos y enlaces
 * en formularios y vistas XMLView. No está vinculado a ningún campo del modelo.
 *
 * Ejemplos de uso en XMLView:
 *
 * <!-- Texto simple -->
 * <widget type="info" text="some-translation-key"/>
 *
 * <!-- Texto con icono -->
 * <widget type="info" icon="fa-solid fa-circle-info" text="some-translation-key"/>
 *
 * <!-- Alerta Bootstrap con icono -->
 * <widget type="info" alert="danger" icon="fa-solid fa-triangle-exclamation" text="delete-warning"/>
 *
 * <!-- Alerta con enlace -->
 * <widget type="info" alert="info" text="need-help" href="https://example.com" btn-text="more-info"/>
 *
 * <!-- Solo enlace (sin texto) -->
 * <widget type="info" href="https://example.com" btn-text="visit-link" btn-class="btn-primary"/>
 *
 * <!-- Vista Twig personalizada (ignora el resto de opciones) -->
 * <widget type="info" template="MyPlugin/MyCustomWidget"/>
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class WidgetInfo extends BaseWidget
{
    /** @var string Tipo de alerta Bootstrap 5 */
    protected $alert;

    /** @var string Clase CSS del botón de enlace */
    protected $btnClass;

    /** @var string Texto del botón de enlace (traducible) */
    protected $btnText;

    /** @var string URL del enlace */
    protected $href;

    /** @var string Ruta a una vista Twig personalizada (sin extensión .html.twig) */
    protected $template;

    /** @var string Texto informativo principal (traducible con Tools::trans()) */
    protected $text;

    /** @var string[] Tipos de alerta Bootstrap 5 válidos */
    const ALERT_TYPES = [
        'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark',
    ];

    /**
     * Clase de botón recomendada según la documentación de Bootstrap 5 para cada tipo de alerta.
     *
     * @var string[]
     */
    const ALERT_BTN_CLASS = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'success' => 'btn-success',
        'danger' => 'btn-danger',
        'warning' => 'btn-warning',
        'info' => 'btn-info',
        'light' => 'btn-secondary',
        'dark' => 'btn-light',
    ];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        // fieldname es opcional en este widget (no está vinculado a un campo del modelo)
        $data['fieldname'] = $data['fieldname'] ?? '';

        parent::__construct($data);

        $this->alert = in_array($data['alert'] ?? '', self::ALERT_TYPES, true) ? $data['alert'] : '';
        $this->btnText = $data['btn-text'] ?? '';
        $this->href = $data['href'] ?? '';
        $this->template = $data['template'] ?? '';
        $this->text = $data['text'] ?? '';

        // clase del botón: personalizada, por defecto según el tipo de alerta, o btn-secondary
        if (isset($data['btn-class'])) {
            $this->btnClass = $data['btn-class'];
        } elseif ($this->alert && isset(self::ALERT_BTN_CLASS[$this->alert])) {
            $this->btnClass = self::ALERT_BTN_CLASS[$this->alert];
        } else {
            $this->btnClass = 'btn-secondary';
        }
    }

    /**
     * Renderiza el widget en modo edición (formulario).
     *
     * @param object $model
     * @param string $title
     * @param string $description
     * @param string $titleurl
     *
     * @return string
     */
    public function edit($model, $title = '', $description = '', $titleurl = ''): string
    {
        // si hay template, renderizar la vista Twig (ignora el resto de opciones)
        if ($this->template) {
            return $this->renderTemplate($model);
        }

        $content = $this->buildContent();
        if (empty($content)) {
            return '';
        }

        return '<div class="mb-3">' . $content . '</div>';
    }

    /**
     * No genera input oculto (el widget no tiene campo de modelo).
     *
     * @param object $model
     *
     * @return string
     */
    public function inputHidden($model): string
    {
        return '';
    }

    /**
     * Muestra el texto traducido en texto plano.
     *
     * @param object $model
     *
     * @return string
     */
    public function plainText($model): string
    {
        return $this->text ? Tools::trans($this->text) : '';
    }

    /**
     * No procesa datos de formulario (widget de solo visualización).
     *
     * @param object $model
     * @param Request $request
     */
    public function processFormData(&$model, $request): void
    {
        // sin campo de modelo asociado, no hay nada que procesar
    }

    /**
     * Renderiza el widget en la celda de una tabla.
     *
     * @param object $model
     * @param string $display
     *
     * @return string
     */
    public function tableCell($model, $display = 'left'): string
    {
        if ($this->template) {
            return '<td>' . $this->renderTemplate($model) . '</td>';
        }

        $text = $this->text ? Tools::trans($this->text) : '';
        $class = $this->combineClasses('text-' . $display, $this->class);
        return '<td class="' . $class . '">' . $text . '</td>';
    }

    /**
     * Construye el HTML completo del contenido del widget.
     *
     * @return string
     */
    protected function buildContent(): string
    {
        $text = $this->text ? Tools::trans($this->text) : '';
        $iconHtml = $this->icon ? '<i class="' . $this->icon . ' fa-fw"></i>' : '';
        $linkHtml = $this->buildLinkHtml();

        if ($this->alert) {
            return $this->buildAlertHtml($iconHtml, $text, $linkHtml);
        }

        return $this->buildPlainHtml($iconHtml, $text, $linkHtml);
    }

    /**
     * Construye el HTML de una alerta Bootstrap 5.
     *
     * @param string $iconHtml
     * @param string $text
     * @param string $linkHtml
     *
     * @return string
     */
    protected function buildAlertHtml(string $iconHtml, string $text, string $linkHtml): string
    {
        $extraClass = $this->class ? ' ' . $this->class : '';
        $html = '<div class="alert alert-' . $this->alert . $extraClass . ' d-flex align-items-start" role="alert">';

        if ($iconHtml) {
            $html .= '<div class="me-2 mt-1 flex-shrink-0">' . $iconHtml . '</div>';
        }

        $html .= '<div class="flex-grow-1">';
        if ($text) {
            $html .= '<div>' . $text . '</div>';
        }
        if ($linkHtml) {
            $html .= '<div class="mt-2">' . $linkHtml . '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Construye el HTML sin alerta (texto simple con icono y enlace opcionales).
     *
     * @param string $iconHtml
     * @param string $text
     * @param string $linkHtml
     *
     * @return string
     */
    protected function buildPlainHtml(string $iconHtml, string $text, string $linkHtml): string
    {
        if (empty($text) && empty($linkHtml)) {
            return '';
        }

        $extraClass = $this->class ? ' ' . $this->class : '';
        $html = '<div class="d-flex align-items-start' . $extraClass . '">';

        if ($iconHtml) {
            $html .= '<span class="me-2 mt-1 flex-shrink-0 text-secondary">' . $iconHtml . '</span>';
        }

        $html .= '<div class="flex-grow-1">';
        if ($text) {
            $html .= '<span>' . $text . '</span>';
        }
        if ($linkHtml) {
            $marginTop = $text ? ' mt-2' : '';
            $html .= '<div class="' . trim($marginTop) . '">' . $linkHtml . '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Devuelve un href seguro para renderizar o cadena vacía si no es válido.
     *
     * Se permiten únicamente URLs absolutas http/https y rutas relativas
     * habituales de la aplicación.
     *
     * @return string
     */
    private function getSafeHref(): string
    {
        $href = trim((string)$this->href);
        if ($href === '') {
            return '';
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);
        if (is_string($scheme) && $scheme !== '') {
            $scheme = strtolower($scheme);
            return in_array($scheme, ['http', 'https'], true) ? $href : '';
        }

        if ($href[0] === '/' || $href[0] === '?' || $href[0] === '#') {
            return $href;
        }

        return preg_match('/^[^:\s]+(?:\/[^:\s]*)?$/', $href) ? $href : '';
    }

    /**
     * Construye el HTML del botón de enlace.
     *
     * @return string
     */
    protected function buildLinkHtml(): string
    {
        $safeHref = $this->getSafeHref();
        if ($safeHref === '') {
            return '';
        }

        $btnText = $this->btnText ? Tools::trans($this->btnText) : $safeHref;
        $btnClass = 'btn btn-sm ' . $this->btnClass;

        // determinar si es un enlace externo para abrir en nueva ventana
        $isExternal = preg_match('/^https?:\/\//i', $safeHref);
        $target = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';

        return '<a href="' . Tools::noHtml($safeHref) . '" class="' . $btnClass . '"' . $target . '>'
            . $btnText
            . '</a>';
    }

    /**
     * Renderiza una vista Twig personalizada pasando el modelo como variable.
     *
     * @param object $model
     *
     * @return string
     */
    protected function renderTemplate($model): string
    {
        try {
            return Html::render($this->template . '.html.twig', ['model' => $model]);
        } catch (Exception $e) {
            Tools::log()->error('widget-info-template-error', [
                '%template%' => $this->template,
                '%error%' => $e->getMessage(),
            ]);

            return '<div class="alert alert-danger mb-3">'
                . '<i class="fa-solid fa-circle-xmark fa-fw me-2"></i>'
                . Tools::noHtml($this->template)
                . '</div>';
        }
    }

    /**
     * Sin campo de modelo vinculado, no hay valor que establecer.
     *
     * @param object $model
     */
    protected function setValue($model): void
    {
        // no hay campo de modelo vinculado
    }
}
