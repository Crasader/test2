<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Blacklist extends \BB\DurianBundle\Entity\Blacklist implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'wholeDomain', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'account', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'identityCard', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'nameReal', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'telephone', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'email', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'ip', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'createdAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'modifiedAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'systemLock', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'controlTerminal');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'wholeDomain', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'account', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'identityCard', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'nameReal', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'telephone', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'email', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'ip', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'createdAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'modifiedAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'systemLock', '' . "\0" . 'BB\\DurianBundle\\Entity\\Blacklist' . "\0" . 'controlTerminal');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Blacklist $proxy) {
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
    public function getDomain()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDomain', array());

        return parent::getDomain();
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
    public function isWholeDomain()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isWholeDomain', array());

        return parent::isWholeDomain();
    }

    /**
     * {@inheritDoc}
     */
    public function setWholeDomain($wholeDomain)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setWholeDomain', array($wholeDomain));

        return parent::setWholeDomain($wholeDomain);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccount()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAccount', array());

        return parent::getAccount();
    }

    /**
     * {@inheritDoc}
     */
    public function setAccount($account)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAccount', array($account));

        return parent::setAccount($account);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentityCard()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIdentityCard', array());

        return parent::getIdentityCard();
    }

    /**
     * {@inheritDoc}
     */
    public function setIdentityCard($identityCard)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIdentityCard', array($identityCard));

        return parent::setIdentityCard($identityCard);
    }

    /**
     * {@inheritDoc}
     */
    public function getNameReal()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getNameReal', array());

        return parent::getNameReal();
    }

    /**
     * {@inheritDoc}
     */
    public function setNameReal($nameReal)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setNameReal', array($nameReal));

        return parent::setNameReal($nameReal);
    }

    /**
     * {@inheritDoc}
     */
    public function getTelephone()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTelephone', array());

        return parent::getTelephone();
    }

    /**
     * {@inheritDoc}
     */
    public function setTelephone($telephone)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setTelephone', array($telephone));

        return parent::setTelephone($telephone);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEmail', array());

        return parent::getEmail();
    }

    /**
     * {@inheritDoc}
     */
    public function setEmail($email)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEmail', array($email));

        return parent::setEmail($email);
    }

    /**
     * {@inheritDoc}
     */
    public function getIp()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIp', array());

        return parent::getIp();
    }

    /**
     * {@inheritDoc}
     */
    public function setIp($ip)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIp', array($ip));

        return parent::setIp($ip);
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
    public function setCreatedAt(\DateTime $createdAt)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCreatedAt', array($createdAt));

        return parent::setCreatedAt($createdAt);
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
    public function setModifiedAt(\DateTime $modifiedAt)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setModifiedAt', array($modifiedAt));

        return parent::setModifiedAt($modifiedAt);
    }

    /**
     * {@inheritDoc}
     */
    public function isSystemLock()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isSystemLock', array());

        return parent::isSystemLock();
    }

    /**
     * {@inheritDoc}
     */
    public function setSystemLock($lock)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSystemLock', array($lock));

        return parent::setSystemLock($lock);
    }

    /**
     * {@inheritDoc}
     */
    public function isControlTerminal()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isControlTerminal', array());

        return parent::isControlTerminal();
    }

    /**
     * {@inheritDoc}
     */
    public function setControlTerminal($control)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setControlTerminal', array($control));

        return parent::setControlTerminal($control);
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
