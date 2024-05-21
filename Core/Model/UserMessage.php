<?php declare(strict_types=1);

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

class UserMessage extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var string */
    public $body;

    /** @var string */
    public $level;

    /** @var bool */
    public $showlater;

    /** @var string */
    protected $nick;

    public function clear()
    {
        parent::clear();

        $this->showlater = false;
    }

    /** @return UserMessage */
    public function showLater()
    {
        $this->showlater = true;
        return $this;
    }

    public function addLinkBtn($url, $texto)
    {
        $this->body .= ' <a class="btn btn-primary btn-sm" href="' . $url . '">' . Tools::lang()->trans($texto) . '</a>';
        return $this;
    }

    public function addActionBtn($viewName, $texto, $accion)
    {
        /** ESTO NO SE COMO HACERLO */
        return $this;
    }

    public function addHelpLink()
    {
        /** ESTE NO SE A QUE TE REFIERES */
        return $this;
    }

    /**
     * @return UserMessage[]
     */
    public function allToShowNow(): array
    {
        // obtenemos los mensajes a mostrar en la siguiente request
        $where = [
            new DataBaseWhere('nick', $this->nick),
            new DataBaseWhere('showlater', true),
        ];
        $messagesShowLater = self::all($where, [], 0, 0);

        // obtenemos los mensajes a mostrar ahora
        $where = [
            new DataBaseWhere('nick', $this->nick),
            new DataBaseWhere('showlater', false),
        ];
        $messagesShowNow = self::all($where, [], 0, 0);

        // eliminamos de la BBDD los mensajes que vamos a mostrar ahora
        // para que no se vuelvan a mostrar
        foreach ($messagesShowNow as $message){
            $message->delete();
        }

        // cambiamos el showlater a false para que se muestren en la proxima request
        foreach ($messagesShowLater as $message){
            $message->showlater = false;
            $message->save();
        }


        return $messagesShowNow;
    }

    public function setUser(string $nick)
    {
        $this->nick = $nick;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'users_messages';
    }
}
