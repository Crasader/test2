<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class PaymentCharge extends \BB\DurianBundle\Entity\PaymentCharge implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'payway', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'name', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'preset', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'rank', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'code', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'depositOnline', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'depositCompany', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'depositMobile', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'depositBitcoin', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'version');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'payway', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'name', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'preset', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'rank', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'code', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'depositOnline', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'depositCompany', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'depositMobile', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'depositBitcoin', '' . "\0" . 'BB\\DurianBundle\\Entity\\PaymentCharge' . "\0" . 'version');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (PaymentCharge $proxy) {
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
    public function getCode()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCode', array());

        return parent::getCode();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', array());

        return parent::getName();
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
    public function getPayway()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPayway', array());

        return parent::getPayway();
    }

    /**
     * {@inheritDoc}
     */
    public function getRank()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRank', array());

        return parent::getRank();
    }

    /**
     * {@inheritDoc}
     */
    public function isPreset()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isPreset', array());

        return parent::isPreset();
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
    public function setCode($code)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCode', array($code));

        return parent::setCode($code);
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setName', array($name));

        return parent::setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function setRank($rank)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRank', array($rank));

        return parent::setRank($rank);
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getVersion', array());

        return parent::getVersion();
    }

    /**
     * {@inheritDoc}
     */
    public function addDepositOnline(\BB\DurianBundle\Entity\DepositOnline $depositOnline)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addDepositOnline', array($depositOnline));

        return parent::addDepositOnline($depositOnline);
    }

    /**
     * {@inheritDoc}
     */
    public function getDepositOnline()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDepositOnline', array());

        return parent::getDepositOnline();
    }

    /**
     * {@inheritDoc}
     */
    public function addDepositCompany(\BB\DurianBundle\Entity\DepositCompany $depositCompany)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addDepositCompany', array($depositCompany));

        return parent::addDepositCompany($depositCompany);
    }

    /**
     * {@inheritDoc}
     */
    public function getDepositCompany()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDepositCompany', array());

        return parent::getDepositCompany();
    }

    /**
     * {@inheritDoc}
     */
    public function addDepositMobile(\BB\DurianBundle\Entity\DepositMobile $depositMobile)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addDepositMobile', array($depositMobile));

        return parent::addDepositMobile($depositMobile);
    }

    /**
     * {@inheritDoc}
     */
    public function getDepositMobile()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDepositMobile', array());

        return parent::getDepositMobile();
    }

    /**
     * {@inheritDoc}
     */
    public function addDepositBitcoin(\BB\DurianBundle\Entity\DepositBitcoin $depositBitcoin)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addDepositBitcoin', array($depositBitcoin));

        return parent::addDepositBitcoin($depositBitcoin);
    }

    /**
     * {@inheritDoc}
     */
    public function getDepositBitcoin()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDepositBitcoin', array());

        return parent::getDepositBitcoin();
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
