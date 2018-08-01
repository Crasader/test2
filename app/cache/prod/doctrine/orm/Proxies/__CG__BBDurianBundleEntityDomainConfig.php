<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class DomainConfig extends \BB\DurianBundle\Entity\DomainConfig implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'name', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'enable', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'removed', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'blockCreateUser', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'blockLogin', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'blockTestUser', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'loginCode', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'verifyOtp', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'freeTransferWallet', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'walletStatus');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'name', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'enable', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'removed', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'blockCreateUser', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'blockLogin', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'blockTestUser', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'loginCode', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'verifyOtp', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'freeTransferWallet', '' . "\0" . 'BB\\DurianBundle\\Entity\\DomainConfig' . "\0" . 'walletStatus');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (DomainConfig $proxy) {
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
    public function getDomain()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getDomain();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDomain', array());

        return parent::getDomain();
    }

    /**
     * {@inheritDoc}
     */
    public function isBlockCreateUser()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isBlockCreateUser', array());

        return parent::isBlockCreateUser();
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
    public function setName($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setName', array($name));

        return parent::setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function remove()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'remove', array());

        return parent::remove();
    }

    /**
     * {@inheritDoc}
     */
    public function isRemoved()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isRemoved', array());

        return parent::isRemoved();
    }

    /**
     * {@inheritDoc}
     */
    public function disable()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'disable', array());

        return parent::disable();
    }

    /**
     * {@inheritDoc}
     */
    public function enable()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'enable', array());

        return parent::enable();
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isEnabled', array());

        return parent::isEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function setBlockCreateUser($blockCreateUser)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBlockCreateUser', array($blockCreateUser));

        return parent::setBlockCreateUser($blockCreateUser);
    }

    /**
     * {@inheritDoc}
     */
    public function isBlockLogin()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isBlockLogin', array());

        return parent::isBlockLogin();
    }

    /**
     * {@inheritDoc}
     */
    public function setBlockLogin($blockLogin)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBlockLogin', array($blockLogin));

        return parent::setBlockLogin($blockLogin);
    }

    /**
     * {@inheritDoc}
     */
    public function isBlockTestUser()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isBlockTestUser', array());

        return parent::isBlockTestUser();
    }

    /**
     * {@inheritDoc}
     */
    public function setBlockTestUser($blockTestUser)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBlockTestUser', array($blockTestUser));

        return parent::setBlockTestUser($blockTestUser);
    }

    /**
     * {@inheritDoc}
     */
    public function getLoginCode()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLoginCode', array());

        return parent::getLoginCode();
    }

    /**
     * {@inheritDoc}
     */
    public function setLoginCode($loginCode)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLoginCode', array($loginCode));

        return parent::setLoginCode($loginCode);
    }

    /**
     * {@inheritDoc}
     */
    public function isVerifyOtp()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isVerifyOtp', array());

        return parent::isVerifyOtp();
    }

    /**
     * {@inheritDoc}
     */
    public function setVerifyOtp($verifyOtp)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setVerifyOtp', array($verifyOtp));

        return parent::setVerifyOtp($verifyOtp);
    }

    /**
     * {@inheritDoc}
     */
    public function enableFreeTransferWallet()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'enableFreeTransferWallet', array());

        return parent::enableFreeTransferWallet();
    }

    /**
     * {@inheritDoc}
     */
    public function disableFreeTransferWallet()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'disableFreeTransferWallet', array());

        return parent::disableFreeTransferWallet();
    }

    /**
     * {@inheritDoc}
     */
    public function isFreeTransferWallet()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isFreeTransferWallet', array());

        return parent::isFreeTransferWallet();
    }

    /**
     * {@inheritDoc}
     */
    public function setWalletStatus($walletStatus)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setWalletStatus', array($walletStatus));

        return parent::setWalletStatus($walletStatus);
    }

    /**
     * {@inheritDoc}
     */
    public function getWalletStatus()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getWalletStatus', array());

        return parent::getWalletStatus();
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