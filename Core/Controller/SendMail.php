<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\User;
use PHPMailer\PHPMailer\Exception;

/**
 * Description of SendMail
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Javier García Iceta      <javigarciaiceta@gmail.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class SendMail extends Controller
{
    const MAX_FILE_AGE = 2592000; // 30 days
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /** @var CodeModel */
    public $codeModel;

    /** @var NewMail */
    public $newMail;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'send-mail';
        $data['icon'] = 'fa-solid fa-envelope';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     * @throws Exception
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->codeModel = new CodeModel();

        $this->newMail = NewMail::create()
            ->setUser($this->user);

        // Check if the email is configurate
        if (false === $this->newMail->canSendMail()) {
            Tools::log()->warning('email-not-configured');
        }

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Return the URL of the actual controller.
     *
     * @return string
     */
    public function url(): string
    {
        $sendParams = ['fileName' => $this->request->get('fileName', '')];
        if (empty($sendParams['fileName'])) {
            return parent::url();
        }

        if ($this->request->get('modelClassName') && $this->request->get('modelCode')) {
            $sendParams['modelClassName'] = $this->request->get('modelClassName');
            $sendParams['modelCode'] = $this->request->get('modelCode');
            if ($this->request->get('modelCodes')) {
                $sendParams['modelCodes'] = urldecode($this->request->get('modelCodes'));
            }
        }

        return parent::url() . '?' . http_build_query($sendParams);
    }

    /**
     * Run the autocomplete action.
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function autocompleteAction(): array
    {
        $results = [];

        $data = $this->requestGet(['source', 'field', 'title', 'term']);
        foreach ($this->codeModel->search($data['source'], $data['field'], $data['title'], $data['term']) as $value) {
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }

        return $results;
    }

    protected function checkInvoices(): void
    {
        if ($this->request->query->get('modelClassName') != 'FacturaCliente') {
            return;
        }

        $invoice = new FacturaCliente();
        if ($invoice->loadFromCode($this->request->query->getAlnum('modelCode')) && $invoice->editable) {
            Tools::log()->warning('sketch-invoice-warning');
        }
    }

    /**
     * Execute main actions.
     *
     * @param string $action
     * @throws Exception
     */
    protected function execAction(string $action): void
    {
        switch ($action) {
            case 'autocomplete':
                $this->setTemplate(false);
                $data = $this->autocompleteAction();
                $this->response->setContent(json_encode($data));
                break;

            case 'send':
                // valid request?
                if (false === $this->validateFormToken()) {
                    break;
                }
                if ($this->send()) {
                    Tools::log()->notice('send-mail-ok');
                    $this->updateFemail();
                    $this->redirAfter();
                    break;
                }
                Tools::log()->error('send-mail-error');
                break;

            default:
                $this->removeOld();
                $this->setEmailAddress();
                $this->setAttachment();
                $this->checkInvoices();
                break;
        }
    }

    protected function getEmails(string $field): array
    {
        return NewMail::splitEmails($this->request->request->get($field, ''));
    }

    protected function loadDataDefault($model): void
    {
        // buscamos el texto de la notificación para usar el asunto y el cuerpo
        $notificationModel = new EmailNotification();
        $where = [
            new DataBaseWhere('name', 'sendmail-' . $model->modelClassName()),
            new DataBaseWhere('enabled', true)
        ];
        if ($notificationModel->loadFromCode('', $where)) {
            $shortCodes = ['{code}', '{name}', '{date}', '{total}', '{number2}'];
            $shortValues = [$model->codigo, $model->nombrecliente, $model->fecha, $model->total, $model->numero2];
            $this->newMail->title = str_replace($shortCodes, $shortValues, $notificationModel->subject);
            $this->newMail->text = str_replace($shortCodes, $shortValues, $notificationModel->body);
            return;
        }

        // si no hay notificación, usamos los datos de las traducciones
        switch ($model->modelClassName()) {
            case 'AlbaranCliente':
                $this->newMail->title = Tools::lang()->trans('delivery-note-email-subject', ['%code%' => $model->codigo]);
                $this->newMail->text = Tools::lang()->trans('delivery-note-email-text', ['%code%' => $model->codigo]);
                break;

            case 'FacturaCliente':
                $this->newMail->title = Tools::lang()->trans('invoice-email-subject', ['%code%' => $model->codigo]);
                $this->newMail->text = Tools::lang()->trans('invoice-email-text', ['%code%' => $model->codigo]);
                break;

            case 'PedidoCliente':
                $this->newMail->title = Tools::lang()->trans('order-email-subject', ['%code%' => $model->codigo]);
                $this->newMail->text = Tools::lang()->trans('order-email-text', ['%code%' => $model->codigo]);
                break;

            case 'PresupuestoCliente':
                $this->newMail->title = Tools::lang()->trans('estimation-email-subject', ['%code%' => $model->codigo]);
                $this->newMail->text = Tools::lang()->trans('estimation-email-text', ['%code%' => $model->codigo]);
                break;
        }
    }

    protected function redirAfter(): void
    {
        $className = self::MODEL_NAMESPACE . $this->request->get('modelClassName');
        if (false === class_exists($className)) {
            Tools::log()->notice('reloading');
            $this->redirect('SendMail', 3);
            return;
        }

        $model = new $className();
        $modelCode = $this->request->get('modelCode');
        if ($model->loadFromCode($modelCode) && property_exists($className, 'femail')) {
            Tools::log()->notice('reloading');
            $this->redirect($model->url(), 3);
        }
    }

    /**
     * Remove old files.
     */
    protected function removeOld(): void
    {
        foreach (glob(FS_FOLDER . '/MyFiles/*_mail_*.pdf') as $fileName) {
            $parts = explode('_', $fileName);
            $time = (int)substr(end($parts), 0, -4);
            if ($time < (time() - self::MAX_FILE_AGE)) {
                unlink($fileName);
            }
        }
    }

    /**
     * Return array with parameters values
     *
     * @param array $keys
     *
     * @return array
     */
    protected function requestGet(array $keys): array
    {
        $result = [];
        foreach ($keys as $value) {
            $result[$value] = $this->request->get($value);
        }

        return $result;
    }

    /**
     * Send and email with data posted from form.
     *
     * @return bool
     * @throws Exception
     */
    protected function send(): bool
    {
        if ($this->newMail->fromEmail != $this->user->email && $this->request->request->get('replyto', '0')) {
            $this->newMail->replyTo($this->user->email, $this->user->nick);
        }

        $this->newMail->title = $this->request->request->get('subject', '');
        $this->newMail->text = $this->request->request->get('body', '');
        $this->newMail->setMailbox($this->request->request->get('email-from', ''));

        foreach ($this->getEmails('email') as $email) {
            $this->newMail->to($email);
        }
        foreach ($this->getEmails('email-cc') as $email) {
            $this->newMail->cc($email);
        }
        foreach ($this->getEmails('email-bcc') as $email) {
            $this->newMail->bcc($email);
        }

        $this->setAttachment();

        return $this->newMail->send();
    }

    /**
     * @throws Exception
     */
    protected function setAttachment(): void
    {
        $fileName = $this->request->get('fileName', '');
        Tools::folderCheckOrCreate(NewMail::ATTACHMENTS_TMP_PATH);
        $this->newMail->addAttachment(FS_FOLDER . '/' . NewMail::ATTACHMENTS_TMP_PATH . $fileName, $fileName);

        foreach ($this->request->files->getArray('uploads') as $file) {
            // guardamos el adjunto en una carpeta temporal
            if ($file->move(NewMail::ATTACHMENTS_TMP_PATH, $file->getClientOriginalName())) {
                // añadimos el adjunto al email
                $filePath = FS_FOLDER . '/' . NewMail::ATTACHMENTS_TMP_PATH . $file->getClientOriginalName();
                $this->newMail->addAttachment($filePath, $file->getClientOriginalName());
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function setEmailAddress(): void
    {
        $email = $this->request->get('email', '');
        if (!empty($email)) {
            $this->newMail->to($email);
            return;
        }

        $className = self::MODEL_NAMESPACE . $this->request->get('modelClassName', '');
        if (false === class_exists($className)) {
            return;
        }

        $model = new $className();
        $model->loadFromCode($this->request->get('modelCode', ''));
        $this->loadDataDefault($model);

        if (property_exists($model, 'email')) {
            $this->newMail->to($model->email);
            return;
        }

        $proveedor = new Proveedor();
        if (property_exists($model, 'codproveedor') && $proveedor->loadFromCode($model->codproveedor) && $proveedor->email) {
            $this->newMail->to($proveedor->email, $proveedor->razonsocial);
            return;
        }

        $contact = new Contacto();
        if (property_exists($model, 'idcontactofact') && $contact->loadFromCode($model->idcontactofact) && $contact->email) {
            $this->newMail->to($contact->email, $contact->fullName());
            return;
        }

        $cliente = new Cliente();
        if (property_exists($model, 'codcliente') && $cliente->loadFromCode($model->codcliente) && $cliente->email) {
            $this->newMail->to($cliente->email, $cliente->razonsocial);
        }
    }

    /**
     * Update the property femail with actual date if exist param ModelClassName and ModelCode.
     */
    protected function updateFemail(): void
    {
        $className = self::MODEL_NAMESPACE . $this->request->get('modelClassName');
        if (false === class_exists($className)) {
            return;
        }

        // marcamos la fecha del envío del email
        $model = new $className();
        $modelCode = $this->request->get('modelCode');
        if ($model->loadFromCode($modelCode) && property_exists($className, 'femail')) {
            $model->femail = Tools::date();
            if (false === $model->save()) {
                Tools::log()->error('record-save-error');
                return;
            }

            // si el sujeto no tiene email, le asignamos el del destinatario
            $subject = $model->getSubject();
            if (empty($subject->email)) {
                foreach ($this->newMail->getToAddresses() as $email) {
                    $subject->email = $email;
                    $subject->save();
                    break;
                }
            }
        }

        // si hay más documentos, marcamos también la fecha de envío
        $modelCodes = $this->request->get('modelCodes', '');
        foreach (explode(',', $modelCodes) as $modelCode) {
            if ($model->loadFromCode($modelCode) && property_exists($className, 'femail')) {
                $model->femail = Tools::date();
                $model->save();
            }
        }
    }
}
