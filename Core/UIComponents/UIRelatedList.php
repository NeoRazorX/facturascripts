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

namespace FacturaScripts\Core\UIComponents;

/**
 * Contenedor ligero de lista relacionada para UIEditController.
 *
 * Representa una pestaña de solo lectura en un formulario de edición que
 * muestra registros de un modelo relacionado en una tabla Bootstrap.
 *
 * La subclase declara la lista con addRelatedList() en buildForm() y la
 * rellena con datos en modifyUI() llamando a setRecords().
 *
 * Uso mínimo:
 *   // En buildForm():
 *   $this->addRelatedList('ListSubcuenta', 'subaccounts', 'fa-solid fa-book')
 *       ->addColumn('codejercicio', 'exercise')
 *       ->addColumn('saldo', 'balance', 'text-end', 'money');
 *
 *   // En modifyUI():
 *   $this->relatedList('ListSubcuenta')?->setRecords(Subcuenta::all($where));
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class UIRelatedList
{
    private string $name;
    private string $title;
    private string $icon;

    /** @var array{field: string, title: string, class: string, format: string}[] */
    private array $columns = [];

    /** @var object[] Registros del modelo a mostrar. */
    private array $records = [];

    /** @var array{action: string, label: string, icon: string, color: string}[] */
    private array $buttons = [];

    public function __construct(string $name, string $title, string $icon = 'fa-solid fa-list')
    {
        $this->name  = $name;
        $this->title = $title;
        $this->icon  = $icon;
    }

    public static function make(string $name, string $title, string $icon = 'fa-solid fa-list'): static
    {
        return new static($name, $title, $icon);
    }

    /**
     * Añade una columna a la tabla de resultados.
     *
     * @param string $field    Nombre de la propiedad del modelo.
     * @param string $titleKey Clave de traducción para la cabecera.
     * @param string $class    Clase CSS adicional de la celda (p. ej. 'text-end').
     * @param string $format   Formato del valor: 'text' | 'money' | 'number'.
     */
    public function addColumn(string $field, string $titleKey, string $class = '', string $format = 'text'): static
    {
        $this->columns[] = [
            'field'  => $field,
            'title'  => $titleKey,
            'class'  => $class,
            'format' => $format,
        ];
        return $this;
    }

    /** Reemplaza la lista de registros a mostrar. */
    public function setRecords(array $records): static
    {
        $this->records = $records;
        return $this;
    }

    /**
     * Añade un botón de acción en la cabecera del panel.
     *
     * El botón genera un formulario POST con _event={action} y el código
     * del registro principal. Solo es visible si hay registros o si se
     * declara explícitamente (útil para el botón "Generar").
     *
     * @param string $action Nombre del evento que se disparará (_event).
     * @param string $label  Clave de traducción de la etiqueta.
     * @param string $icon   Clase FontAwesome del icono.
     * @param string $color  Variante Bootstrap del botón (success, primary, etc.).
     */
    public function addButton(string $action, string $label, string $icon = 'fa-solid fa-bolt', string $color = 'primary'): static
    {
        $this->buttons[] = [
            'action' => $action,
            'label'  => $label,
            'icon'   => $icon,
            'color'  => $color,
        ];
        return $this;
    }

    public function name(): string   { return $this->name; }
    public function title(): string  { return $this->title; }
    public function icon(): string   { return $this->icon; }
    public function columns(): array { return $this->columns; }
    public function records(): array { return $this->records; }
    public function buttons(): array { return $this->buttons; }
}
