<?php

namespace FacturaScripts\Core\Base\Cache;

/**
 * CacheItem define un objeto dentro de la caché.
 */
class CacheItem
{
    /**
     * Valor de la clave del objeto de la cache
     * @var string 
     */
    private $key;
    
    /**
     * Contnido almacenado en el objeto de la cache
     * @var mixed 
     */
    private $value;
    
    /**
     * Fecha de expiracion del objeto.
     * @var DateInterval 
     */
    private $expiration;
    
    /**
     * Constructor por defecto
     * 
     * @param type $key Clave del objeto
     * @param type $value Contenido del objeto
     * @param type $expiration Fecha de expiración del objeto
     */
    public function __construct($key= NULL, $value=NULL, $expiration=NULL) {

        $this->key=$key;
        $this->value=$value;
        $this->expiration=$expiration;
    }
    /**
     * Devuelve la clave de este objeto de la caché.
     *
     *
     * @return string La clave de este objeto de caché.
     */
    public function getKey(){
        return $this->key;

    }

    /**
     * Devuelve el valor del objeto asociado a esta clave de caché.
     *  
     * @return mixed El valor que corresponde a este objeto de caché o NULL si no se encuentra.
     */
    public function get(){
        return $this->value;
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool
     *   True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit(){
        if($time>$this->expiration){
            return false;
        }
        if ($this->value==NULL){
            return false;
        }
        return true;
    }

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set($value){
        $this->value=$value;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param \DateTimeInterface|null $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAt($expiration){
        $this->expiration= $expiration;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval|null $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter($time){
        $this->expiration= time() + $time;
        return $this;
    }
}