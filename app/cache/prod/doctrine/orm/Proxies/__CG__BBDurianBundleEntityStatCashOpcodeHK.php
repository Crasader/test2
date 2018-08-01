<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class StatCashOpcodeHK extends \BB\DurianBundle\Entity\StatCashOpcodeHK implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'at', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'userId', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'currency', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'parentId', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'opcode', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'amount', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'count');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'at', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'userId', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'currency', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'parentId', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'opcode', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'amount', '' . "\0" . 'BB\\DurianBundle\\Entity\\StatCashOpcodeHK' . "\0" . 'count');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (StatCashOpcodeHK $proxy) {
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
    public function setId($id)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setId', array($id));

        return parent::setId($id);
    }

    /**
     * {@inheritDoc}
     */
    public function getAt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAt', array());

        return parent::getAt();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserId', array());

        return parent::getUserId();
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
    public function setDomain($domain)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDomain', array($domain));

        return parent::setDomain($domain);
    }

    /**
     * {@inheritDoc}
     */
    public function getDomain()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDomain', array());

        return parent::getDomain();
    }

    /**
     * {@inheritDoc}
     */
    public function setParentId($parentId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setParentId', array($parentId));

        return parent::setParentId($parentId);
    }

    /**
     * {@inheritDoc}
     */
    public function getParentId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getParentId', array());

        return parent::getParentId();
    }

    /**
     * {@inheritDoc}
     */
    public function setOpcode($opcode)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOpcode', array($opcode));

        return parent::setOpcode($opcode);
    }

    /**
     * {@inheritDoc}
     */
    public function getOpcode()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOpcode', array());

        return parent::getOpcode();
    }

    /**
     * {@inheritDoc}
     */
    public function setAmount($amount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAmount', array($amount));

        return parent::setAmount($amount);
    }

    /**
     * {@inheritDoc}
     */
    public function addAmount($amount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addAmount', array($amount));

        return parent::addAmount($amount);
    }

    /**
     * {@inheritDoc}
     */
    public function getAmount()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAmount', array());

        return parent::getAmount();
    }

    /**
     * {@inheritDoc}
     */
    public function setCount($count)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCount', array($count));

        return parent::setCount($count);
    }

    /**
     * {@inheritDoc}
     */
    public function addCount($count = 1)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addCount', array($count));

        return parent::addCount($count);
    }

    /**
     * {@inheritDoc}
     */
    public function getCount()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCount', array());

        return parent::getCount();
    }

}
