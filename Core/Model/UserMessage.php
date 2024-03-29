<?php declare(strict_types=1);

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Tools;

class UserMessage
{
    /** @var string */
    public $body;

    /** @var string */
    public $level;

    /** @var bool */
    public $showLater;

    /** @var string */
    protected $userNick;

    /** @var string */
    protected $path;

    /** @var string */
    protected $fileName;

    /** @var string */
    protected $filePath;

    public function __construct(string $userNick)
    {
        $this->userNick = $userNick;
        $this->showLater = false;

        $this->path = Tools::folder('MyFiles', 'Tmp', 'UserMessages');
        $this->fileName = $this->userNick . '-UserMessages.json';
        $this->filePath = $this->path . DIRECTORY_SEPARATOR . $this->fileName;

        if (false === is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }

        if (false === is_file($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    /** @return UserMessage $this */
    public function showLater()
    {
        $this->showLater = true;
        return $this;
    }

    public function addLinkBtn($url, $texto): void
    {

    }

    public function addHelpLink(): void
    {

    }

    public function save(): void
    {
        $messages = $this->getMessagesFromFile();

        array_push($messages, $this);

        $this->storeMessagesToFile($messages);
    }

    public function allShowNow()
    {
        $messages = $this->getMessagesFromFile();

        if(count($messages) === 0){
            return $messages;
        }

        // Filtramos los mensajes que hay que mostrar ahora.
        $messagesShowNow = array_filter($messages, function ($message) {
            return false === $message['showLater'];
        });

        // Filtramos los mensajes que hay que mostrar en la proxima request.
        $messagesShowLater = array_filter($messages, function ($message) {
            return $message['showLater'];
        });

        // Cambiamos showLater a false para que se borren en la proxima request.
        $messagesShowLater = array_map(function ($message){
            $message['showLater'] = false;
            return $message;
        }, $messagesShowLater);

        // Guardamos en archivo los mensajes que mostraremos en la siguiente request.
        $this->storeMessagesToFile($messagesShowLater);

        return $messagesShowNow;
    }

    /** @return UserMessage[] array */
    protected function getMessagesFromFile(): array
    {
        $messages = [];

        $fileContent = file_get_contents($this->filePath);
        if (false === $fileContent){
            return $messages;
        }

        $jsonContent = json_decode($fileContent, true);
        if(false === is_array($jsonContent)){
            return $messages;
        }

        return $jsonContent;
    }

    /** @param UserMessage[] $messages */
    protected function storeMessagesToFile(array $messages): void
    {
        $jsonContent = json_encode($messages);
        if ($jsonContent){
            file_put_contents($this->filePath, $jsonContent);
        }
    }
}
