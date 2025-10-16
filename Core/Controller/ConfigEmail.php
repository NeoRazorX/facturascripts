<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\EmailNotification;

/**
 * Controller to edit main settings
 *
 * @author Daniel Fernández Giménez  <hola@danielfg.es>
 * @author Carlos Garcia Gomez       <carlos@facturascripts.com>
 */
class ConfigEmail extends PanelController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'email';
        $pageData['icon'] = 'fa-solid fa-envelope';
        return $pageData;
    }

    /**
     * Crea un nuevo png para en la carpeta especificada sobreescribiendo el antiguo
     * 
     * @param int $fileId el id del attached file
     * @param string $targetPath Debe terminar en .png y válido para gd
     * 
     * @return bool
     */
    protected function createNewPng(int $fileId, string $targetPath) : bool
    {
        $attached = new AttachedFile();
        if(false === $attached->load($fileId) || false === $attached->isImage()) {
            // no existe o no es una imagen
            Tools::log()->error('image-not-valid-or-not-exist');
            return false;
        }

        // borrar si existe un archivo antiguo
        if(is_file($targetPath)) {
            @unlink($targetPath);
        }

        // crear imagen png con gd
        $imageData = file_get_contents($attached->getFullPath());
        $image = imagecreatefromstring($imageData);
        if(false === $image) {
            Tools::log()->error('image-create-error');
            return false;
        }

        if (false === imagepng($image, $targetPath)) {
            Tools::log()->error('image-convert-to-png-error');
            @unlink($targetPath);
            return false;
        }

        // Limpiar la imagen si es válida
        if ($image instanceof \GdImage || is_resource($image)) {
            imagedestroy($image);
        }

        return true;
    }

    protected function createViews()
    {
        $this->setTemplate('EditSettings');
        $this->createViewsEmail();
        $this->createViewsEmailSent();
        $this->createViewsEmailNotification();
    }

    protected function createViewsEmail(string $viewName = 'ConfigEmail'): void
    {
        $this->addEditView($viewName, 'Settings', 'email', 'fa-solid fa-envelope');

        // desactivamos los botones nuevo y eliminar
        $this->tab($viewName)
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);
    }

    protected function createViewsEmailNotification(string $viewName = 'ListEmailNotification'): void
    {
        $this->addListView($viewName, 'EmailNotification', 'notifications', 'fa-solid fa-bell')
            ->addSearchFields(['body', 'name', 'subject'])
            ->addOrderBy(['date'], 'date')
            ->addOrderBy(['name'], 'name', 1);

        // filtros
        $this->listView($viewName)->addFilterCheckbox('enabled');

        // desactivamos el botón nuevo
        $this->tab($viewName)->setSettings('btnNew', false);

        // añadimos los botones de activar y desactivar
        $this->addButton($viewName, [
            'action' => 'enable-notification',
            'color' => 'success',
            'icon' => 'fa-solid fa-check-square',
            'label' => 'enable'
        ]);

        $this->addButton($viewName, [
            'action' => 'disable-notification',
            'color' => 'warning',
            'icon' => 'fa-regular fa-square',
            'label' => 'disable'
        ]);
    }

    protected function createViewsEmailSent(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fa-solid fa-paper-plane')
            ->addSearchFields(['addressee', 'body', 'subject'])
            ->addOrderBy(['date'], 'date', 2);

        // filtros
        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->listView($viewName)->addFilterSelect('nick', 'user', 'nick', $users);

        $from = $this->codeModel->all('emails_sent', 'email_from', 'email_from');
        $this->listView($viewName)->addFilterSelect('from', 'from', 'email_from', $from);

        $this->listView($viewName)
            ->addFilterPeriod('date', 'period', 'date', true)
            ->addFilterCheckbox('opened')
            ->addFilterCheckbox('attachment', 'has-attachments');

        // desactivamos el botón nuevo
        $this->tab($viewName)->setSettings('btnNew', false);
    }

    protected function enableNotificationAction(bool $value): void
    {
        if (false === $this->validateFormToken()) {
            return;
        } elseif (false === $this->user->can('EditEmailNotification', 'update')) {
            Tools::log()->warning('not-allowed-modify');
            return;
        }

        $codes = $this->request->request->getArray('codes');
        if (false === is_array($codes)) {
            return;
        }

        foreach ($codes as $code) {
            $notification = new EmailNotification();
            if (false === $notification->load($code)) {
                continue;
            }

            $notification->enabled = $value;
            if (false === $notification->save()) {
                Tools::log()->warning('record-save-error');
                return;
            }
        }

        Tools::log()->notice('record-updated-correctly');
    }

    /**
     * Run the controller after actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        parent::execAfterAction($action);
        if ($action === 'testmail') {
            $this->testMailAction();
        }
    }

    /**
     * @param string $action
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'disable-notification':
                $this->enableNotificationAction(false);
                break;

            case 'enable-notification':
                $this->enableNotificationAction(true);
                break;
        }

        return parent::execPreviousAction($action);
    }

    protected function editAction(): bool
    {
        // Asegúrate de que la acción de edición es válida
        if (false === parent::editAction()) {
            return false;
        }

        // revisar si existe un logo existente
        $publicFolder = Tools::folder('MyFiles', 'Public');
        Tools::folderCheckOrCreate($publicFolder);
        $targetPath = $publicFolder . DIRECTORY_SEPARATOR . 'email-logo.png';
        $existsImageLogo = is_file($targetPath);

        // Obtener el ID del nuevo logo o igual que el anterior
        $inputLogo = $this->request->request->getInt('id-email-logo', 0);
        $idLogo = Tools::settings('email', 'id-email-logo', 0);
        $cambiarLogo = false;
        $borrarLogo = false;

        // si no hay logo seleccionado borrar logo
        if ($inputLogo === 0) {
            $borrarLogo = $existsImageLogo;
            // si no se ha seleccionado ninguno ($idLogo === 0)
        }elseif($inputLogo !== $idLogo){
            $cambiarLogo = true;
            Tools::settingsSet('email', 'id-email-logo', $inputLogo);
            // reconstruir logo si no existe y debería existír
        }elseif($idLogo !== 0){
            $cambiarLogo = !$existsImageLogo;
        }

        // borrar logo
        if($borrarLogo){
            unlink($targetPath);
        }

        // sobreescribir logo
        if($cambiarLogo){
            if(false === $this->createNewPng($inputLogo, $targetPath)){
                Tools::log()->warning('email-logo-create-error');
                return false;
            }

            // avisar de que la instalación es local
            if ($this->isOfflineInstallation()) {
                Tools::log()->warning('email-logo-offline-warning');
            }

        }

        return true;
    }

    protected function loadData($viewName, $view)
    {
        $this->hasData = true;

        switch ($viewName) {
            case 'ConfigEmail':
                $view->loadData('email');
                $view->model->name = 'email';
                $this->loadMailerValues($viewName);
                if ($view->model->mailer === 'SMTP') {
                    // añadimos el botón test
                    $this->addButton($viewName, [
                        'action' => 'testmail',
                        'color' => 'info',
                        'icon' => 'fa-solid fa-envelope',
                        'label' => 'test'
                    ]);
                }
                break;

            case 'ListEmailNotification':
            case 'ListEmailSent':
                $view->loadData();
                break;
        }
    }

    private function isOfflineInstallation(): bool
    {
        $siteUrl = Tools::siteUrl();
        $host = (string)parse_url($siteUrl, PHP_URL_HOST);

        // si termina en localhost, local o empieza con 127
        if (str_ends_with($host, 'localhost')
            || str_ends_with($host, 'local')
            || str_starts_with($host, '127.')
        ) {
            return true;
        }

        return false;
    }

    protected function loadMailerValues(string $viewName)
    {
        $column = $this->views[$viewName]->columnForName('mailer');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArray(NewMail::getMailer(), true, false);
        }
    }

    protected function testMailAction(): void
    {
        // guardamos los datos del formulario primero
        if (false === $this->editAction()) {
            return;
        }

        $email = new NewMail();
        if ($email->test()) {
            Tools::log()->notice('mail-test-ok');
            return;
        }

        Tools::log()->warning('mail-test-error');
    }
}
