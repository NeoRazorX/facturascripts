<?php declare(strict_types=1);

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;

class LogAudit extends Base\ModelClass
{
    use Base\ModelTrait;

    public const MAX_MESSAGE_LEN = 3000;

    /** @var string */
    public $channel;

    /** @var string */
    public $context;

    /** @var int */
    public $id;

    /** @var int */
    public $idcontacto;

    /** @var string */
    public $ip;

    /** @var string */
    public $level;

    /** @var string */
    public $message;

    /** @var string */
    public $model;

    /** @var string */
    public $modelcode;

    /** @var string */
    public $nick;

    /** @var string */
    public $time;

    /** @var string */
    public $uri;

    public function clear(): void
    {
        parent::clear();
        $this->time = Tools::dateTime();
    }

    /**
     * Returns the saved context as array.
     *
     * @return array
     */
    public function context(): array
    {
        return json_decode(Tools::fixHtml($this->context), true);
    }

    public function delete(): bool
    {
        Tools::log()->warning('cant-delete-audit-log');
        return false;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'logs_audit';
    }

    public function test(): bool
    {
        $this->channel = Tools::noHtml($this->channel);
        $this->context = Tools::noHtml($this->context);
        $this->message = Tools::noHtml($this->message);
        if (strlen($this->message) > static::MAX_MESSAGE_LEN) {
            $this->message = substr($this->message, 0, static::MAX_MESSAGE_LEN);
        }

        $this->model = Tools::noHtml($this->model);
        $this->modelcode = Tools::noHtml((string)$this->modelcode);
        $this->uri = Tools::noHtml($this->uri);

        return parent::test();
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->level = 'warning';
        $this->log($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->level = 'info';
        $this->log($message, $context);
    }

    private function log(string $message, array $context = []): void
    {
        $this->channel = self::AUDIT_CHANNEL;
        $this->context = $this->getContext($context);
        $this->idcontacto = $context['idcontacto'] ?? null;
        $this->ip = Session::getClientIp();
        $this->message = Tools::lang()->trans($message, $context);
        $this->model = $context['model-class'] ?? null;
        $this->modelcode = $context['model-code'] ?? null;
        $this->nick = $context['nick'] ?? Session::user()->nick;
        $this->time = isset($context['time']) ? date('d-m-Y H:i:s', (int)$context['time']) : date('d-m-Y H:i:s');
        $this->uri = $context['uri'] ?? Session::get('uri');
        $this->save();
    }

    protected function saveUpdate(array $values = []): bool
    {
        Tools::log()->warning('cant-update-audit-log');
        return false;
    }

    private function getContext(array $context)
    {
        return json_encode($context);
    }
}
