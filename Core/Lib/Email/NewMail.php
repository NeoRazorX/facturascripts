<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Html;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Dinamic\Model\EmailSent;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\User;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Description of NewMail
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class NewMail
{
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
    private static $template = 'NewTemplate.html.twig';

    /** @var bool */
    protected $lowsecure;

    /** @var PHPMailer */
    protected $mail;

    /** @var BaseBlock[] */
    protected $mainBlocks = [];

    /** @var string */
    public $signature;

    /** @var string */
    public $text;

    /** @var string */
    public $title;

    /** @var string */
    public $verificode;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $appSettings = $this->toolBox()->appSettings();

        $this->empresa = new Empresa();
        $this->empresa->loadFromCode($appSettings->get('default', 'idempresa'));

        $this->fromEmail = $appSettings->get('email', 'email');
        $this->fromName = $this->empresa->nombrecorto;

        $this->mail = new PHPMailer();
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mail->Mailer = $appSettings->get('email', 'mailer');

        $this->mail->SMTPSecure = $appSettings->get('email', 'enc', '');
        if ($this->mail->SMTPSecure) {
            $this->mail->SMTPAuth = true;
            $this->mail->AuthType = $appSettings->get('email', 'authtype', '');
        }

        $this->mail->Host = $appSettings->get('email', 'host');
        $this->mail->Port = $appSettings->get('email', 'port');
        $this->mail->Username = $appSettings->get('email', 'user') ?
            $appSettings->get('email', 'user') :
            $appSettings->get('email', 'email');
        $this->mail->Password = $appSettings->get('email', 'password');
        $this->lowsecure = (bool)$appSettings->get('email', 'lowsecure');

        foreach (static::splitEmails($appSettings->get('email', 'emailcc', '')) as $email) {
            $this->addCC($email);
        }

        foreach (static::splitEmails($appSettings->get('email', 'emailbcc', '')) as $email) {
            $this->addBCC($email);
        }

        $this->signature = $appSettings->get('email', 'signature', '');
        $this->verificode = $this->toolBox()->utils()->randomString(20);
    }

    /**
     * @throws Exception
     */
    public function addAddress(string $email, string $name = '')
    {
        $this->mail->addAddress($email, $name);
    }

    /**
     * Add attachments to the email.
     *
     * @throws Exception
     */
    public function addAttachment(string $path, string $name)
    {
        $this->mail->addAttachment($path, $name);
    }

    /**
     * @throws Exception
     */
    public function addBCC(string $email, string $name = '')
    {
        $this->mail->addBCC($email, $name);
    }

    /**
     * @throws Exception
     */
    public function addCC(string $email, string $name = '')
    {
        $this->mail->addCC($email, $name);
    }

    public function addFooterBlock(BaseBlock $block): NewMail
    {
        $block->setVerificode($this->verificode);
        $this->footerBlocks[] = $block;
        return $this;
    }

    public function addMainBlock(BaseBlock $block): NewMail
    {
        $block->setVerificode($this->verificode);
        $this->mainBlocks[] = $block;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function addReplyTo(string $address, string $name = '')
    {
        $this->mail->addReplyTo($address, $name);
    }

    /**
     * Check if the email is configured
     */
    public function canSendMail(): bool
    {
        return !empty($this->fromEmail) && !empty($this->mail->Password) && !empty($this->mail->Host);
    }

    public function getAttachmentNames(): array
    {
        $names = [];
        foreach ($this->mail->getAttachments() as $attach) {
            $names[] = $attach[1];
        }

        return $names;
    }

    /**
     * Returns an array with available email trays
     */
    public function getAvailableMailboxes(): array
    {
        return empty($this->fromEmail) ? [] : [$this->fromEmail];
    }

    public function getBCCAddresses(): array
    {
        $addresses = [];
        foreach ($this->mail->getBccAddresses() as $addr) {
            $addresses[] = $addr[0];
        }

        return $addresses;
    }

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

    public function getToAddresses(): array
    {
        $addresses = [];
        foreach ($this->mail->getToAddresses() as $addr) {
            $addresses[] = $addr[0];
        }

        return $addresses;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws Exception
     */
    public function send(): bool
    {
        if (false === $this->canSendMail()) {
            $this->toolBox()->i18nLog()->warning('email-not-configured');
            return false;
        }

        $this->mail->setFrom($this->fromEmail, $this->fromName);
        $this->mail->Subject = $this->title;
        $this->mail->msgHTML($this->renderHTML());

        if ('smtp' === $this->mail->Mailer && false === $this->mail->smtpConnect($this->smtpOptions())) {
            $this->toolBox()->i18nLog()->warning('mail-server-error');
            return false;
        }

        if ($this->mail->send()) {
            $this->saveMailSent();
            return true;
        }

        $this->toolBox()->i18nLog()->error('error', ['%error%' => $this->mail->ErrorInfo]);
        return false;
    }

    /**
     * @throws Exception
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function sendNotification(string $notificationName, array $params): bool
    {
        // ¿La notificación existe?
        $notification = new EmailNotification();
        if (false === $notification->loadFromCode($notificationName)) {
            ToolBox::i18nLog()->warning('email-notification-not-exists', ['%name%' => $notificationName]);
            return false;
        }

        // ¿Está desactivada?
        if (false === $notification->enabled) {
            ToolBox::i18nLog()->warning('email-notification-disabled', ['%name%' => $notificationName]);
            return false;
        }

        if (!isset($params['verificode'])) {
            $params['verificode'] = $this->verificode;
        }

        $this->title = MailNotifier::getText($notification->subject, $params);
        $this->text = MailNotifier::getText($notification->body, $params);

        return $this->send();
    }

    public function setMailbox(string $emailFrom)
    {
        $this->fromEmail = $emailFrom;
    }

    public static function setTemplate(string $template)
    {
        static::$template = $template;
    }

    public function setUser(User $user)
    {
        $this->fromNick = $user->nick;
    }

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
     * Test the PHPMailer connection. Return the result of the connection.
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
                $this->toolBox()->i18nLog()->warning('test-' . $this->mail->Mailer . '-not-implemented');
                return false;
        }
    }

    protected function getFooterBlocks(): array
    {
        $signature = $this->toolBox()->utils()->fixHtml($this->signature);
        return empty($signature)
            ? $this->footerBlocks
            : array_merge($this->footerBlocks, [new TextBlock($signature, 'text-footer')]);
    }

    protected function getMainBlocks(): array
    {
        return empty($this->text)
            ? $this->mainBlocks
            : array_merge([new TextBlock($this->text, 'pb-15')], $this->mainBlocks);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    protected function renderHTML(): string
    {
        $params = [
            'company' => $this->empresa,
            'footerBlocks' => $this->getFooterBlocks(),
            'mainBlocks' => $this->getMainBlocks(),
            'title' => $this->title
        ];
        return Html::render('Email/' . static::$template, $params);
    }

    protected function saveMailSent()
    {
        // get all email address
        $addresses = array_merge($this->getToAddresses(), $this->getCcAddresses(), $this->getBccAddresses());

        // save email sent
        foreach (array_unique($addresses) as $address) {
            $emailSent = new EmailSent();
            $emailSent->addressee = $address;
            $emailSent->body = $this->text;
            $emailSent->nick = $this->fromNick;
            $emailSent->subject = $this->title;
            $emailSent->verificode = $this->verificode;
            $emailSent->save();
        }
    }

    /**
     * Returns the SMTP Options.
     *
     * @return array
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

    protected function toolBox(): ToolBox
    {
        return new ToolBox();
    }
}
