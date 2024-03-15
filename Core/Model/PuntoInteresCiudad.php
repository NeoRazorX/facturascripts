<?php
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Session;

class PuntoInteresCiudad extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $alias;

    /** @var string */
    public $creation_date;

    /** @var int */
    public $id;

    /** @var int */
    public $idciudad;

    /** @var string */
    public $last_nick;

    /** @var string */
    public $last_update;

    /** @var float */
    public $latitud;

    /** @var float */
    public $longitud;

    /** @var string */
    public $name;

    /** @var string */
    public $nick;

    public function clear() 
    {
        parent::clear();
        $this->idciudad = 0;
        $this->latitud = 0.0;
        $this->longitud = 0.0;
    }

    public static function primaryColumn(): string
    {
        return "id";
    }

    public static function tableName(): string
    {
        return "puntos_interes_ciudades";
    }

    public function test(): bool
    {
        $this->creation_date = $this->creationdate ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;
        $this->alias = Tools::noHtml($this->alias);
        $this->name = Tools::noHtml($this->name);
        return parent::test();
    }

    protected function saveUpdate(array $values = [])
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();
        return parent::saveUpdate($values);
    }
}
