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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\UI\Event\UIEvent;
use FacturaScripts\Core\Lib\UI\Event\UIResponse;
use FacturaScripts\Core\Lib\UI\Field;
use FacturaScripts\Core\Lib\UI\UIButton;
use FacturaScripts\Core\Lib\UI\UICard;
use FacturaScripts\Core\Lib\UI\UIController;
use FacturaScripts\Core\Lib\UI\UIDropdown;
use FacturaScripts\Core\Lib\UI\UIForm;
use FacturaScripts\Core\Lib\UI\UIGroup;
use FacturaScripts\Core\Lib\UI\UIInfoBox;
use FacturaScripts\Core\Lib\UI\UIModal;
use FacturaScripts\Core\Lib\UI\UIPage;
use FacturaScripts\Core\Lib\UI\UITabs;
use FacturaScripts\Core\Lib\UI\Validation\ErrorBag;
use FacturaScripts\Core\Tools;

/**
 * Página de demostración del sistema de UI Components.
 *
 * Muestra varios formularios independientes (cada uno con su propio ciclo de
 * submit/validación AJAX), pestañas, grupos, selects con carga remota, una
 * cascada país→provincia, validación cross-field, un modal server-rendered y
 * eventos de página que actualizan fragmentos.
 *
 * No persiste datos — los eventos emiten un notice de confirmación.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewDashboardUI extends UIController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'ui-components-demo';
        $data['icon'] = 'fa-solid fa-flask';
        return $data;
    }

    protected function buildUI(UIPage $page): void
    {
        // --- Formulario de cabecera, independiente del panel de pestañas ---
        $page->add(
            UIForm::make('header')->title('demo-header-fields')
                ->add(
                    Field::text('titulo')->label('title')->placeholder('Escribe un título de prueba')->setCols(6),
                    Field::number('cantidad')->label('quantity')->decimals(0)->min(0)->setCols(3),
                    Field::date('fecha')->label('date')->setCols(3),
                    UIButton::submit('save')
                )
                ->onSubmit(function (UIEvent $e, UIResponse $r) {
                    $this->logValues($e, ['titulo', 'cantidad', 'fecha']);
                    $r->rerender($e->form());
                })
        );

        // --- Panel con sub-pestañas dentro de una tarjeta ---
        $tabs = UITabs::make('demo');
        $page->add(
            UICard::make('panel')->title('Panel con sub-pestañas')->icon('fa-solid fa-table-columns')->add($tabs)
        );

        $this->buildGeneralTab($tabs);
        $this->buildOptionsTab($tabs);
        $this->buildNotesTab($tabs);
        $this->buildActionsTab($page, $tabs);
    }

    private function buildGeneralTab(UITabs $tabs): void
    {
        $general = $tabs->tab('general', 'General', 'fa-solid fa-circle-info');

        // cada bloque con guardado propio es un form independiente; el nombre
        // de campo solo debe ser único dentro de su form
        $general->add(
            UIForm::make('identification')->title('Identificación')
                ->add(
                    Field::text('nombre')->label('name')->required()->setCols(5),
                    Field::text('codigo')->label('code')->setCols(3),
                    Field::number('precio')->label('price')->decimals(2)->setCols(2),
                    UIButton::submit('save')->color('outline-primary')->setCols(2)
                )
                ->addCheck(function (UIForm $form, ErrorBag $errors) {
                    if ((float)$form->value('precio') > 0 && empty($form->value('codigo'))) {
                        $errors->add('codigo', 'El código es obligatorio cuando hay precio.');
                    }
                })
                ->onSubmit(function (UIEvent $e, UIResponse $r) {
                    $this->logValues($e, ['nombre', 'codigo', 'precio']);
                    $r->rerender($e->form());
                })
        );

        $general->add(
            UIForm::make('dates')->title('Fechas y ubicación')
                ->add(
                    Field::date('fecha_inicio')->label('start-date')->setCols(3),
                    Field::date('fecha_fin')->label('end-date')->setCols(3),
                    // cascada: al cambiar el país se re-renderiza el select de provincia
                    Field::select('pais')->label('country')
                        ->fromCodeModel('Pais', 'codpais', 'nombre')->setCols(2),
                    Field::select('provincia')->label('province')
                        ->fromCodeModel('Provincia', 'idprovincia', 'provincia')
                        ->dependsOn('pais', 'codpais')->setCols(2),
                    UIButton::submit('save')->color('outline-primary')->setCols(2)
                )
                ->addCheck(function (UIForm $form, ErrorBag $errors) {
                    $inicio = $form->value('fecha_inicio');
                    $fin = $form->value('fecha_fin');
                    if (!empty($inicio) && !empty($fin) && $fin < $inicio) {
                        $errors->add('fecha_fin', 'La fecha final no puede ser anterior a la inicial.');
                    }
                })
                ->onSubmit(function (UIEvent $e, UIResponse $r) {
                    $this->logValues($e, ['fecha_inicio', 'fecha_fin', 'pais', 'provincia']);
                    $r->rerender($e->form());
                })
        );
    }

    private function buildOptionsTab(UITabs $tabs): void
    {
        $opciones = $tabs->tab('opciones', 'Opciones', 'fa-solid fa-sliders');

        $opciones->add(
            UIForm::make('settings')->title('Ajustes de visibilidad')
                ->add(
                    UIGroup::make('checks')->alignBottom()->add(
                        Field::checkbox('activo')->label('active'),
                        Field::checkbox('destacado')->label('featured'),
                        Field::checkbox('visible')->label('visible')->setValue(true),
                        UIButton::submit('save')->color('outline-primary')
                    )
                )
                ->onSubmit(function (UIEvent $e, UIResponse $r) {
                    $this->logValues($e, ['activo', 'destacado', 'visible']);
                    $r->rerender($e->form());
                })
        );

        $opciones->add(
            UIForm::make('classification')->title('Clasificación')
                ->add(
                    Field::select('tipo')->label('type')->options([
                        'A' => 'Tipo A',
                        'B' => 'Tipo B',
                        'C' => 'Tipo C',
                    ])->setCols(4),
                    Field::select('estado')->label('status')->options([
                        'borrador' => 'Borrador',
                        'publicado' => 'Publicado',
                        'archivado' => 'Archivado',
                    ])->setCols(4),
                    // select2 remoto: busca contra el endpoint _ui_query del componente
                    Field::select('pais')->label('country')
                        ->searchable('Pais', 'codpais', 'nombre')
                        ->placeholder('Buscar país…')->setCols(2),
                    UIButton::submit('save')->color('outline-primary')->setCols(2)
                )
                ->onSubmit(function (UIEvent $e, UIResponse $r) {
                    $this->logValues($e, ['tipo', 'estado', 'pais']);
                    $r->rerender($e->form());
                })
        );
    }

    private function buildNotesTab(UITabs $tabs): void
    {
        $notas = $tabs->tab('notas', 'Notas', 'fa-solid fa-note-sticky');

        $form = UIForm::make('notes')->title('Texto libre')
            ->add(
                Field::textarea('observaciones')->label('observations')->rows(4)->setCols(12),
                Field::textarea('notas_internas')->label('internal-notes')->rows(3)->setCols(12),
                UIButton::submit('save')->color('outline-primary'),
                UIButton::make('clear')->label('Limpiar')->icon('fa-solid fa-eraser')
                    ->color('outline-danger')->action('clear')
                    ->confirm('¿Borrar el contenido de las notas?')
            )
            ->onSubmit(function (UIEvent $e, UIResponse $r) {
                $this->logValues($e, ['observaciones', 'notas_internas']);
                $r->rerender($e->form());
            });

        // evento sin validación: vacía los campos y re-renderiza el form
        $form->on('clear', function (UIEvent $e, UIResponse $r) {
            $e->form()->field('observaciones')->setValue(null);
            $e->form()->field('notas_internas')->setValue(null);
            $r->notice('Notas vaciadas.')->rerender($e->form());
        });

        $notas->add($form);
    }

    private function buildActionsTab(UIPage $page, UITabs $tabs): void
    {
        $acciones = $tabs->tab('acciones', 'Acciones', 'fa-solid fa-bolt');

        // infobox actualizable por evento de página (fragmento)
        $counterBox = UIInfoBox::make('info_counter')
            ->title('Documentos pendientes')
            ->text((string)random_int(10, 99))
            ->icon('fa-solid fa-file-invoice')
            ->color('primary')
            ->setCols(4);

        $acciones->add(
            UIGroup::make('info_cards')->title('Tarjetas informativas')->add(
                UIInfoBox::make('info_ok')
                    ->title('Sistema operativo')
                    ->text('Todos los servicios funcionan correctamente.')
                    ->icon('fa-solid fa-circle-check')->color('success')->setCols(4),
                UIInfoBox::make('info_warn')
                    ->title('Aviso de mantenimiento')
                    ->text('Se realizará mantenimiento el próximo domingo.')
                    ->icon('fa-solid fa-triangle-exclamation')->color('warning')->setCols(4),
                $counterBox
            ),
            UIGroup::make('action_buttons')->title('Botones de acción')->add(
                UIButton::make('btn_refresh')->label('Actualizar contador')
                    ->icon('fa-solid fa-rotate')->color('primary')
                    ->pageAction('refresh_counter'),
                UIButton::make('btn_modal')->label('Abrir modal')
                    ->icon('fa-solid fa-window-restore')->color('outline-info')
                    ->pageAction('open_contact'),
                UIButton::make('btn_link')->label('Ver documentación')
                    ->icon('fa-solid fa-book')->color('outline-secondary')
                    ->link('https://facturascripts.com/comunidad')
            ),
            UIGroup::make('dropdown_actions')->title('Desplegables')->add(
                UIDropdown::make('drop_export')->label('Exportar')
                    ->icon('fa-solid fa-file-export')->color('secondary')
                    ->item('CSV', '#', 'fa-solid fa-file-csv')
                    ->item('PDF', '#', 'fa-solid fa-file-pdf')
                    ->divider()
                    ->item('Excel', '#', 'fa-solid fa-file-excel'),
                UIDropdown::make('drop_ops')->label('Operaciones')
                    ->icon('fa-solid fa-gears')->color('outline-primary')
                    ->itemPageAction('Duplicar', 'demo_duplicate', 'fa-solid fa-copy')
                    ->itemPageAction('Archivar', 'demo_archive', 'fa-solid fa-box-archive')
            )
        );

        // modal server-rendered con su propio form
        $page->add(
            UIModal::make('contact_modal')->title('Contacto rápido')->icon('fa-solid fa-address-card')
                ->add(
                    UIForm::make('contact')
                        ->add(
                            Field::text('nombre')->label('name')->required()->setCols(12),
                            Field::text('email')->label('email')->rule('email')->setCols(12),
                            UIButton::submit('save')->setCols(12)
                        )
                        ->onSubmit(function (UIEvent $e, UIResponse $r) {
                            $this->logValues($e, ['nombre', 'email']);
                            $r->closeModal('contact_modal');
                        })
                )
        );

        // eventos de página
        $page->on('refresh_counter', function (UIEvent $e, UIResponse $r) use ($counterBox) {
            $counterBox->text((string)random_int(10, 99));
            $r->notice('Contador actualizado.')->rerender($counterBox);
        });

        $page->on('open_contact', function (UIEvent $e, UIResponse $r) {
            $r->openModal('contact_modal');
        });

        $page->on('demo_duplicate', fn(UIEvent $e, UIResponse $r) => $r->notice('Acción duplicar ejecutada.'));
        $page->on('demo_archive', fn(UIEvent $e, UIResponse $r) => $r->notice('Acción archivar ejecutada.'));
    }

    /** Emite un notice con los valores actuales de los campos indicados del form del evento. */
    private function logValues(UIEvent $event, array $fields): void
    {
        $parts = [];
        foreach ($fields as $name) {
            $field = $event->form()?->field($name);
            if ($field !== null) {
                $parts[] = $field->labelText() . ': <strong>' . htmlspecialchars($field->displayValue()) . '</strong>';
            }
        }
        Tools::log()->notice(
            empty($parts) ? 'Guardado sin valores.' : 'Guardado — ' . implode(' &nbsp;|&nbsp; ', $parts)
        );
    }
}
