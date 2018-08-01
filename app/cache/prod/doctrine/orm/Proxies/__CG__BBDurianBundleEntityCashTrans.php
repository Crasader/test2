<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class CashTrans extends \BB\DurianBundle\Entity\CashTrans implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'cashId', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'userId', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'currency', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'opcode', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'amount', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'refId', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'createdAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'checked', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'checkedAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'memo');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'cashId', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'userId', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'currency', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'opcode', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'amount', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'refId', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'createdAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'checked', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'checkedAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\CashTrans' . "\0" . 'memo');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (CashTrans $proxy) {
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
            return  parent::getId();
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
    public function getCashId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCashId', array());

        return parent::getCashId();
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
    public function getUserId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserId', array());

        return parent::getUserId();
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
    public function getAmount()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAmount', array());

        return parent::getAmount();
    }

    /**
     * {@inheritDoc}
     */
    public function getRefId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRefId', array());

        return parent::getRefId();
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedAt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCreatedAt', array());

        return parent::getCreatedAt();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreatedAt($createAt)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCreatedAt', array($createAt));

        return parent::setCreatedAt($createAt);
    }

    /**
     * {@inheritDoc}
     */
    public function setMemo($memo)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setMemo', array($memo));

        return parent::setMemo($memo);
    }

    /**
     * {@inheritDoc}
     */
    public function getMemo()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getMemo', array());

        return parent::getMemo();
    }

    /**
     * {@inheritDoc}
     */
    public function getCheckedAt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCheckedAt', array());

        return parent::getCheckedAt();
    }

    /**
     * {@inheritDoc}
     */
    public function isChecked()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isChecked', array());

        return parent::isChecked();
    }

    /**
     * {@inheritDoc}
     */
    public function setRefId($refId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRefId', array($refId));

        return parent::setRefId($refId);
    }

}
