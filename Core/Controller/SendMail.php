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
use Symfony\Component\HttpFoundation\Response;

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
        $data['icon'] = 'fas fa-envelope';
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
        $this->newMail = new NewMail();
        $this->newMail->setUser($this->user);

        // Check if the email is configurate
        if (false === $this->newMail->canSendMail()) {
            $this->toolBox()->i18nLog()->warning('email-not-configured');
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

    protected function checkInvoices()
    {
        if ($this->request->query->get('modelClassName') != 'FacturaCliente') {
            return;
        }

        $invoice = new FacturaCliente();
        if ($invoice->loadFromCode($this->request->query->getAlnum('modelCode')) && $invoice->editable) {
            self::toolBox()::i18nLog()->warning('sketch-invoice-warning');
        }
    }

    /**
     * Execute main actions.
     *
     * @param string $action
     * @throws Exception
     */
    protected function execAction(string $action)
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
                    $this->toolBox()->i18nLog()->notice('send-mail-ok');
                    $this->updateFemail();
                    $this->redirAfter();
                    break;
                }
                $this->toolBox()->i18nLog()->error('send-mail-error');
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

    protected function loadDataDefault($model)
    {
        // buscamos el texto de la notificación para usar el asunto y el cuerpo
        $notificationModel = new EmailNotification();
        $where = [
            new DataBaseWhere('name', 'sendmail-' . $model->modelClassName()),
            new DataBaseWhere('enabled', true)
        ];
        if ($notificationModel->loadFromCode('', $where)) {
            $shortCodes = ['{code}', '{name}', '{date}', '{total}'];
            $shortValues = [$model->codigo, $model->nombrecliente, $model->fecha, $model->total];
            $this->newMail->title = str_replace($shortCodes, $shortValues, $notificationModel->subject);
            $this->newMail->text = str_replace($shortCodes, $shortValues, $notificationModel->body);
            return;
        }

        // si no hay notificación, usamos los datos de las traducciones
        switch ($model->modelClassName()) {
            case 'AlbaranCliente':
                $this->newMail->title = $this->toolBox()->i18n()->trans('delivery-note-email-subject', ['%code%' => $model->codigo]);
                $this->newMail->text = $this->toolBox()->i18n()->trans('delivery-note-email-text', ['%code%' => $model->codigo]);
                break;

            case 'FacturaCliente':
                $this->newMail->title = $this->toolBox()->i18n()->trans('invoice-email-subject', ['%code%' => $model->codigo]);
                $this->newMail->text = $this->toolBox()->i18n()->trans('invoice-email-text', ['%code%' => $model->codigo]);
                break;

            case 'PedidoCliente':
                $this->newMail->title = $this->toolBox()->i18n()->trans('order-email-subject', ['%code%' => $model->codigo]);
                $this->newMail->text = $this->toolBox()->i18n()->trans('order-email-text', ['%code%' => $model->codigo]);
                break;

            case 'PresupuestoCliente':
                $this->newMail->title = $this->toolBox()->i18n()->trans('estimation-email-subject', ['%code%' => $model->codigo]);
                $this->newMail->text = $this->toolBox()->i18n()->trans('estimation-email-text', ['%code%' => $model->codigo]);
                break;
        }
    }

    protected function redirAfter()
    {
        $className = self::MODEL_NAMESPACE . $this->request->get('modelClassName');
        if (false === class_exists($className)) {
            return;
        }

        $model = new $className();
        $modelCode = $this->request->get('modelCode');
        if ($model->loadFromCode($modelCode) && property_exists($className, 'femail')) {
            $this->toolBox()->i18nLog()->notice('reloading');
            $this->redirect($model->url(), 3);
        }
    }

    /**
     * Remove old files.
     */
    protected function removeOld()
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
            $this->newMail->addReplyTo($this->user->email, $this->user->nick);
        }

        $this->newMail->title = $this->request->request->get('subject', '');
        $this->newMail->text = $this->request->request->get('body', '');
        $this->newMail->setMailbox($this->request->request->get('email-from', ''));

        foreach ($this->getEmails('email') as $email) {
            $this->newMail->addAddress($email);
        }
        foreach ($this->getEmails('email-cc') as $email) {
            $this->newMail->addCC($email);
        }
        foreach ($this->getEmails('email-bcc') as $email) {
            $this->newMail->addBCC($email);
        }

        $this->setAttachment();
        foreach ($this->request->files->get('uploads', []) as $file) {
            $this->newMail->addAttachment($file->getPathname(), $file->getClientOriginalName());
        }

        if (false === $this->newMail->send()) {
            return false;
        }

        $fileName = $this->request->get('fileName', '');
        if (file_exists(FS_FOLDER . '/MyFiles/' . $fileName)) {
            unlink(FS_FOLDER . '/MyFiles/' . $fileName);
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function setAttachment()
    {
        $fileName = $this->request->get('fileName', '');
        $this->newMail->addAttachment(FS_FOLDER . '/MyFiles/' . $fileName, $fileName);
    }

    /**
     * @throws Exception
     */
    protected function setEmailAddress()
    {
        $className = self::MODEL_NAMESPACE . $this->request->get('modelClassName', '');
        if (false === class_exists($className)) {
            return;
        }

        $model = new $className();
        $model->loadFromCode($this->request->get('modelCode', ''));
        $this->loadDataDefault($model);

        if (property_exists($model, 'email')) {
            $this->newMail->addAddress($model->email);
            return;
        }

        $proveedor = new Proveedor();
        if (property_exists($model, 'codproveedor') && $proveedor->loadFromCode($model->codproveedor) && $proveedor->email) {
            $this->newMail->addAddress($proveedor->email, $proveedor->razonsocial);
            return;
        }

        $contact = new Contacto();
        if (property_exists($model, 'idcontactofact') && $contact->loadFromCode($model->idcontactofact) && $contact->email) {
            $this->newMail->addAddress($contact->email, $contact->fullName());
            return;
        }

        $cliente = new Cliente();
        if (property_exists($model, 'codcliente') && $cliente->loadFromCode($model->codcliente) && $cliente->email) {
            $this->newMail->addAddress($cliente->email, $cliente->razonsocial);
        }
    }

    /**
     * Update the property femail with actual date if exist param ModelClassName and ModelCode.
     */
    protected function updateFemail()
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
                $this->toolBox()->i18nLog()->error('record-save-error');
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
