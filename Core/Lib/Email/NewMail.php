<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Email;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\HtmlBlock as DinHtmlBlock;
use FacturaScripts\Dinamic\Lib\Email\TextBlock as DinTextBlock;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Dinamic\Model\EmailSent;
use FacturaScripts\Dinamic\Model\Empresa;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Description of NewMail
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class NewMail
{
    const ATTACHMENTS_TMP_PATH = 'MyFiles/Tmp/Email/';

    /** @var Empresa */
    public $empresa;

    /** @var string */
    public $fromEmail;

    /** @var string */
    public $fromName;

    /** @var string */
    public $fromNick;

    /** @var BaseBlock[] */
    protected $footerBlocks = [];

    /** @var string */
    protected $html;

    /** @var bool */
    protected $lowsecure;

    /** @var PHPMailer */
    protected $mail;

    /** @var BaseBlock[] */
    protected $mainBlocks = [];

    /** @var string */
    public $signature;

    /** @var string */
    protected static $template = 'NewTemplate.html.twig';

    /** @var string */
    public $text;

    /** @var string */
    public $title;

    /** @var string */
    public $verificode;

    public function __construct()
    {
        $this->empresa = Empresas::default();

        $this->fromEmail = Tools::settings('email', 'email');
        $this->fromName = $this->empresa->nombrecorto;

        $this->mail = new PHPMailer();
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mail->Mailer = Tools::settings('email', 'mailer');

        $this->mail->SMTPSecure = Tools::settings('email', 'enc', '');
        if ($this->mail->SMTPSecure) {
            $this->mail->SMTPAuth = true;
            $this->mail->AuthType = Tools::settings('email', 'authtype', '');
        }

        $this->mail->Host = Tools::settings('email', 'host');
        $this->mail->Port = Tools::settings('email', 'port');
        $this->mail->Username = Tools::settings('email', 'user') ?
            Tools::settings('email', 'user') :
            Tools::settings('email', 'email');
        $this->mail->Password = Tools::settings('email', 'password');
        $this->lowsecure = (bool)Tools::settings('email', 'lowsecure');

        foreach (static::splitEmails(Tools::settings('email', 'emailcc', '')) as $email) {
            $this->cc($email);
        }

        foreach (static::splitEmails(Tools::settings('email', 'emailbcc', '')) as $email) {
            $this->bcc($email);
        }

        $this->signature = Tools::settings('email', 'signature', '');
        $this->verificode = Tools::randomString(20);
    }

    /**
     * @deprecated since version 2023.09
     */
    public function addAddress(string $email, string $name = ''): NewMail
    {
        return $this->to($email, $name);
    }

    /**
     * Añade un adjunto al correo.
     *
     * @throws Exception
     */
    public function addAttachment(string $path, string $name): NewMail
    {
        $this->mail->addAttachment($path, $name);

        return $this;
    }

    /**
     * @deprecated since version 2023.09
     */
    public function addBCC(string $email, string $name = ''): NewMail
    {
        return $this->bcc($email, $name);
    }

    /**
     * @deprecated since version 2023.09
     */
    public function addCC(string $email, string $name = ''): NewMail
    {
        return $this->cc($email, $name);
    }

    /**
     * Añade un bloque al pie del correo.
     */
    public function addFooterBlock(BaseBlock $block): NewMail
    {
        $block->setVerificode($this->verificode);
        $this->footerBlocks[] = $block;

        return $this;
    }

    /**
     * Añade un bloque al cuerpo del correo.
     */
    public function addMainBlock(BaseBlock $block): NewMail
    {
        $block->setVerificode($this->verificode);
        $this->mainBlocks[] = $block;

        return $this;
    }

    /**
     * @deprecated since version 2023.09
     */
    public function addReplyTo(string $address, string $name = ''): NewMail
    {
        return $this->replyTo($address, $name);
    }

    public function bcc(string $email, string $name = ''): NewMail
    {
        $this->mail->addBCC($email, $name);

        return $this;
    }

    public function body(string $body): NewMail
    {
        $this->text = $body;

        return $this;
    }

    /**
     * Verifica si se puede enviar el correo.
     */
    public function canSendMail(): bool
    {
        return !empty($this->fromEmail) && !empty($this->mail->Password) && !empty($this->mail->Host);
    }

    public function cc(string $email, string $name = ''): NewMail
    {
        $this->mail->addCC($email, $name);

        return $this;
    }

    public static function create(): NewMail
    {
        return new static();
    }

    public static function getAttachmentPath(?string $email, string $folder): string
    {
        $path = 'MyFiles/Email/{{email}}/' . $folder . '/';
        return str_replace('{{email}}', $email, $path);
    }

    /**
     * Devuelve los nombres de los archivos adjuntos.
     */
    public function getAttachmentNames(): array
    {
        $names = [];
        foreach ($this->mail->getAttachments() as $attach) {
            $names[] = $attach[1];
        }

        return $names;
    }

    /**
     * Devuelve un array con los emails disponibles para el usuario.
     */
    public function getAvailableMailboxes(): array
    {
        return empty($this->fromEmail) ? [] : [$this->fromEmail];
    }

    /**
     * Devuelve un array con los emails con copia oculta.
     */
    public function getBCCAddresses(): array
    {
        $addresses = [];
        foreach ($this->mail->getBccAddresses() as $addr) {
            $addresses[] = $addr[0];
        }

        return $addresses;
    }

    /**
     * Devuelve un array con los emails con copia.
     */
    public function getCCAddresses(): array
    {
        $addresses = [];
        foreach ($this->mail->getCcAddresses() as $addr) {
            $addresses[] = $addr[0];
        }

        return $addresses;
    }

    public static function getTemplate(): string
    {
        return static::$template;
    }

    /**
     * Devuelve un array con los emails hacia donde va el mensaje.
     */
    public function getToAddresses(): array
    {
        $addresses = [];
        foreach ($this->mail->getToAddresses() as $addr) {
            $addresses[] = $addr[0];
        }

        return $addresses;
    }

    public function replyTo(string $address, string $name = ''): NewMail
    {
        $this->mail->addReplyTo($address, $name);

        return $this;
    }

    /**
     * Envía el correo.
     *
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function send(): bool
    {
        if (false === $this->canSendMail()) {
            Tools::log()->warning('email-not-configured');
            return false;
        }

        if (false === $this->checkHourlyLimit()) {
            return false;
        }

        $this->mail->setFrom($this->fromEmail, $this->fromName);
        $this->mail->Subject = $this->title;

        $this->renderHTML();
        $this->mail->msgHTML($this->html);

        if ('smtp' === $this->mail->Mailer && false === $this->mail->smtpConnect($this->smtpOptions())) {
            Tools::log()->warning('mail-server-error');
            return false;
        }

        if ($this->mail->send()) {
            $this->saveMailSent();
            return true;
        }

        Tools::log()->error('error', ['%error%' => $this->mail->ErrorInfo]);
        return false;
    }

    /**
     * @throws Exception
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @deprecated since version 2023.09
     */
    public function sendNotification(string $notificationName, array $params): bool
    {
        // ¿La notificación existe?
        $notification = new EmailNotification();
        if (false === $notification->loadFromCode($notificationName)) {
            Tools::log()->warning('email-notification-not-exists', ['%name%' => $notificationName]);
            return false;
        }

        // ¿Está desactivada?
        if (false === $notification->enabled) {
            Tools::log()->warning('email-notification-disabled', ['%name%' => $notificationName]);
            return false;
        }

        if (!isset($params['verificode'])) {
            $params['verificode'] = $this->verificode;
        }

        $this->title = MailNotifier::getText($notification->subject, $params);
        $this->text = MailNotifier::getText($notification->body, $params);

        return $this->send();
    }

    public function setMailbox(string $emailFrom): NewMail
    {
        $this->fromEmail = $emailFrom;

        return $this;
    }

    public function subject(string $subject): NewMail
    {
        $this->title = $subject;

        return $this;
    }

    public static function setTemplate(string $template): void
    {
        static::$template = $template;
    }

    /**
     * Establece el usuario que manda el email.
     */
    public function setUser(User $user): NewMail
    {
        $this->fromNick = $user->nick;

        return $this;
    }

    /**
     * Separa los emails de una cadena en array.
     */
    public static function splitEmails(string $emails): array
    {
        $return = [];
        foreach (explode(',', $emails) as $part) {
            $email = trim($part);
            if (!empty($part)) {
                $return[] = $email;
            }
        }

        return $return;
    }

    /**
     * Pruebe la conexión PHPMailer.
     *
     * @throws Exception
     */
    public function test(): bool
    {
        switch ($this->mail->Mailer) {
            case 'smtp':
                $this->mail->SMTPDebug = 3;
                return $this->mail->smtpConnect($this->smtpOptions());

            default:
                Tools::log()->warning('test-' . $this->mail->Mailer . '-not-implemented');
                return false;
        }
    }

    public function to(string $email, string $name = ''): NewMail
    {
        $this->mail->addAddress($email, $name);

        return $this;
    }

    protected function checkHourlyLimit(): bool
    {
        // calculamos cuantos emails se han enviado en la última hora
        $model = new EmailSent();
        $whereLastHour = [new DataBaseWhere('date', Tools::dateTime('-1 hour'), '>=')];
        $total = $model->count($whereLastHour);

        // si se ha superado el límite, no enviamos el email
        if ($total >= Tools::config('max_emails_hour', 1000)) {
            Tools::log()->warning('hourly-email-limit-reached');
            return false;
        }

        return true;
    }

    /**
     * Devuelve los bloques del pie del correo.
     */
    protected function getFooterBlocks(): array
    {
        $signature = Tools::fixHtml($this->signature);
        return empty($signature)
            ? $this->footerBlocks
            : array_merge($this->footerBlocks, [new TextBlock($signature, 'text-footer')]);
    }

    /**
     * Devuelve los bloques del cuerpo del correo.
     */
    protected function getMainBlocks(): array
    {
        // si no hay texto, devolvemos los bloques principales
        if (empty($this->text)) {
            return $this->mainBlocks;
        }

        // buscamos si en el texto hay algo de html
        $textWhitoutHtml = strip_tags($this->text);
        if ($textWhitoutHtml !== $this->text) {
            return array_merge([new DinHtmlBlock(nl2br($this->text))], $this->mainBlocks);
        }

        // si no hay html, devolvemos el texto como bloque de texto
        return array_merge([new DinTextBlock($this->text, 'pb-15')], $this->mainBlocks);
    }

    /**
     * Renderiza el HTML del correo.
     *
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    protected function renderHTML(): void
    {
        $this->html = Html::render('Email/' . static::$template, [
            'company' => $this->empresa,
            'footerBlocks' => $this->getFooterBlocks(),
            'mainBlocks' => $this->getMainBlocks(),
            'title' => $this->title
        ]);
    }

    /**
     * Guarda el correo enviado en la base de datos.
     */
    protected function saveMailSent(): void
    {
        // Obtiene todas las direcciones de correo electrónico
        $addresses = array_merge($this->getToAddresses(), $this->getCcAddresses(), $this->getBccAddresses());

        // Generamos un identificador único para el correo electrónico
        $uuid = uniqid();

        // obtenemos los adjuntos
        $attachments = $this->mail->getAttachments();

        // guardar correo electrónico enviado
        foreach (array_unique($addresses) as $address) {
            $emailSent = new EmailSent();
            $emailSent->addressee = $address;
            $emailSent->attachment = count($attachments) > 0;
            $emailSent->body = $this->text;
            $emailSent->email_from = $this->fromEmail;
            $emailSent->html = $this->html;
            $emailSent->nick = $this->fromNick;
            $emailSent->subject = $this->title;
            $emailSent->uuid = $uuid;
            $emailSent->verificode = $this->verificode;
            $emailSent->save();
        }

        // si no hay adjuntos, terminamos
        if (empty($attachments)) {
            return;
        }

        // creamos la carpeta de adjuntos para el email
        $path = FS_FOLDER . '/' . static::getAttachmentPath($this->fromEmail, 'Sent') . $uuid . '/';
        Tools::folderCheckOrCreate($path);

        foreach ($attachments as $attach) {
            $newPath = $path . $attach[1];

            // movemos los adjuntos de la carpeta temporal a la carpeta de adjuntos del email
            $tmpPath = FS_FOLDER . '/' . static::ATTACHMENTS_TMP_PATH . $attach[1];
            if (file_exists($tmpPath)) {
                rename($tmpPath, $newPath);
                continue;
            }

            // si el adjunto está fuera de la carpeta temporal, lo copiamos
            $currentPath = FS_FOLDER . '/' . $attach[0];
            if (file_exists($currentPath)) {
                copy($currentPath, $newPath);
            }
        }
    }

    /**
     * Devuelve las opciones SMTP.
     */
    protected function smtpOptions(): array
    {
        if ($this->lowsecure) {
            return [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        return [];
    }
}
