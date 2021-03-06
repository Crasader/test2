<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Exchange extends \BB\DurianBundle\Entity\Exchange implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array();



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'currency', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'buy', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'sell', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'basic', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'activeAt');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'currency', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'buy', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'sell', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'basic', '' . "\0" . 'BB\\DurianBundle\\Entity\\Exchange' . "\0" . 'activeAt');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Exchange $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', array());

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrency($currency)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCurrency', array($currency));

        return parent::setCurrency($currency);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrency()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCurrency', array());

        return parent::getCurrency();
    }

    /**
     * {@inheritDoc}
     */
    public function setBuy($buy)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBuy', array($buy));

        return parent::setBuy($buy);
    }

    /**
     * {@inheritDoc}
     */
    public function getBuy()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBuy', array());

        return parent::getBuy();
    }

    /**
     * {@inheritDoc}
     */
    public function setSell($sell)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSell', array($sell));

        return parent::setSell($sell);
    }

    /**
     * {@inheritDoc}
     */
    public function getSell()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSell', array());

        return parent::getSell();
    }

    /**
     * {@inheritDoc}
     */
    public function setBasic($basic)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBasic', array($basic));

        return parent::setBasic($basic);
    }

    /**
     * {@inheritDoc}
     */
    public function getBasic()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBasic', array());

        return parent::getBasic();
    }

    /**
     * {@inheritDoc}
     */
    public function setActiveAt($activeAt)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setActiveAt', array($activeAt));

        return parent::setActiveAt($activeAt);
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveAt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getActiveAt', array());

        return parent::getActiveAt();
    }

    /**
     * {@inheritDoc}
     */
    public function convertByBasic($amount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'convertByBasic', array($amount));

        return parent::convertByBasic($amount);
    }

    /**
     * {@inheritDoc}
     */
    public function reconvertByBasic($amount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'reconvertByBasic', array($amount));

        return parent::reconvertByBasic($amount);
    }

    /**
     * {@inheritDoc}
     */
    public function reconvertByBuy($amount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'reconvertByBuy', array($amount));

        return parent::reconvertByBuy($amount);
    }

    /**
     * {@inheritDoc}
     */
    public function convertBySell($amount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'convertBySell', array($amount));

        return parent::convertBySell($amount);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'toArray', array());

        return parent::toArray();
    }

}
