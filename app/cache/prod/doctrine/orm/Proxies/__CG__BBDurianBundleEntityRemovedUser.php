<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class RemovedUser extends \BB\DurianBundle\Entity\RemovedUser implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'userId', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'parentId', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'username', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'alias', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'role', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'sub', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'enable', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'block', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'bankrupt', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'test', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'hiddenTest', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'rent', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'size', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'errNum', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'currency', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'createdAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'modifiedAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'lastLogin', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'lastBank', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'password', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'passwordExpireAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'passwordReset', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'removedCash', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'removedCashFake', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'removedCredits', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'removedCards');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'userId', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'parentId', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'username', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'alias', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'role', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'sub', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'enable', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'block', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'bankrupt', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'test', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'hiddenTest', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'rent', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'size', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'errNum', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'currency', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'createdAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'modifiedAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'lastLogin', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'lastBank', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'password', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'passwordExpireAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'passwordReset', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'removedCash', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'removedCashFake', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'removedCredits', '' . "\0" . 'BB\\DurianBundle\\Entity\\RemovedUser' . "\0" . 'removedCards');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (RemovedUser $proxy) {
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
    public function getUserId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getUserId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserId', array());

        return parent::getUserId();
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
    public function getUsername()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUsername', array());

        return parent::getUsername();
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
    public function getAlias()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAlias', array());

        return parent::getAlias();
    }

    /**
     * {@inheritDoc}
     */
    public function isSub()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isSub', array());

        return parent::isSub();
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
    public function getModifiedAt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getModifiedAt', array());

        return parent::getModifiedAt();
    }

    /**
     * {@inheritDoc}
     */
    public function setModifiedAt($modifiedAt)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setModifiedAt', array($modifiedAt));

        return parent::setModifiedAt($modifiedAt);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastLogin()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLastLogin', array());

        return parent::getLastLogin();
    }

    /**
     * {@inheritDoc}
     */
    public function getLastBank()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLastBank', array());

        return parent::getLastBank();
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
    public function getRole()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRole', array());

        return parent::getRole();
    }

    /**
     * {@inheritDoc}
     */
    public function isEnable()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isEnable', array());

        return parent::isEnable();
    }

    /**
     * {@inheritDoc}
     */
    public function isBlock()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isBlock', array());

        return parent::isBlock();
    }

    /**
     * {@inheritDoc}
     */
    public function isBankrupt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isBankrupt', array());

        return parent::isBankrupt();
    }

    /**
     * {@inheritDoc}
     */
    public function isTest()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isTest', array());

        return parent::isTest();
    }

    /**
     * {@inheritDoc}
     */
    public function isHiddenTest()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isHiddenTest', array());

        return parent::isHiddenTest();
    }

    /**
     * {@inheritDoc}
     */
    public function isRent()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isRent', array());

        return parent::isRent();
    }

    /**
     * {@inheritDoc}
     */
    public function getSize()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSize', array());

        return parent::getSize();
    }

    /**
     * {@inheritDoc}
     */
    public function getErrNum()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getErrNum', array());

        return parent::getErrNum();
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPassword', array());

        return parent::getPassword();
    }

    /**
     * {@inheritDoc}
     */
    public function getPasswordExpireAt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPasswordExpireAt', array());

        return parent::getPasswordExpireAt();
    }

    /**
     * {@inheritDoc}
     */
    public function isPasswordReset()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isPasswordReset', array());

        return parent::isPasswordReset();
    }

    /**
     * {@inheritDoc}
     */
    public function addRemovedCash(\BB\DurianBundle\Entity\RemovedCash $removedCash)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addRemovedCash', array($removedCash));

        return parent::addRemovedCash($removedCash);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemovedCash()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRemovedCash', array());

        return parent::getRemovedCash();
    }

    /**
     * {@inheritDoc}
     */
    public function addRemovedCashFake(\BB\DurianBundle\Entity\RemovedCashFake $removedCashFake)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addRemovedCashFake', array($removedCashFake));

        return parent::addRemovedCashFake($removedCashFake);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemovedCashFake()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRemovedCashFake', array());

        return parent::getRemovedCashFake();
    }

    /**
     * {@inheritDoc}
     */
    public function addRemovedCredit(\BB\DurianBundle\Entity\RemovedCredit $removedCredit)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addRemovedCredit', array($removedCredit));

        return parent::addRemovedCredit($removedCredit);
    }

    /**
     * {@inheritDoc}
     */
    public function getRemovedCredits()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRemovedCredits', array());

        return parent::getRemovedCredits();
    }

    /**
     * {@inheritDoc}
     */
    public function getRemovedCard()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRemovedCard', array());

        return parent::getRemovedCard();
    }

    /**
     * {@inheritDoc}
     */
    public function addRemovedCard(\BB\DurianBundle\Entity\RemovedCard $removedCard)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addRemovedCard', array($removedCard));

        return parent::addRemovedCard($removedCard);
    }

    /**
     * {@inheritDoc}
     */
    public function setRole($role)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRole', array($role));

        return parent::setRole($role);
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
