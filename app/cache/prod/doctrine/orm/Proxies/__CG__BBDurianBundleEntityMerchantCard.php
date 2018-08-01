<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class MerchantCard extends \BB\DurianBundle\Entity\MerchantCard implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\MerchantCard' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\MerchantCard' . "\0" . 'paymentMethod', '' . "\0" . 'BB\\DurianBundle\\Entity\\MerchantCard' . "\0" . 'paymentVendor');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\MerchantCard' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\MerchantCard' . "\0" . 'paymentMethod', '' . "\0" . 'BB\\DurianBundle\\Entity\\MerchantCard' . "\0" . 'paymentVendor');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (MerchantCard $proxy) {
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
    public function addPaymentMethod(\BB\DurianBundle\Entity\PaymentMethod $method)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addPaymentMethod', array($method));

        return parent::addPaymentMethod($method);
    }

    /**
     * {@inheritDoc}
     */
    public function removePaymentMethod(\BB\DurianBundle\Entity\PaymentMethod $method)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removePaymentMethod', array($method));

        return parent::removePaymentMethod($method);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethod()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPaymentMethod', array());

        return parent::getPaymentMethod();
    }

    /**
     * {@inheritDoc}
     */
    public function addPaymentVendor(\BB\DurianBundle\Entity\PaymentVendor $vendor)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addPaymentVendor', array($vendor));

        return parent::addPaymentVendor($vendor);
    }

    /**
     * {@inheritDoc}
     */
    public function removePaymentVendor(\BB\DurianBundle\Entity\PaymentVendor $vendor)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removePaymentVendor', array($vendor));

        return parent::removePaymentVendor($vendor);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentVendor()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPaymentVendor', array());

        return parent::getPaymentVendor();
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'toArray', array());

        return parent::toArray();
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
    public function setPaymentGateway(\BB\DurianBundle\Entity\PaymentGateway $paymentGateway)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPaymentGateway', array($paymentGateway));

        return parent::setPaymentGateway($paymentGateway);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentGateway()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPaymentGateway', array());

        return parent::getPaymentGateway();
    }

    /**
     * {@inheritDoc}
     */
    public function setAlias($alias)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAlias', array($alias));

        return parent::setAlias($alias);
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
    public function setNumber($number)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setNumber', array($number));

        return parent::setNumber($number);
    }

    /**
     * {@inheritDoc}
     */
    public function getNumber()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getNumber', array());

        return parent::getNumber();
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
    public function disable()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'disable', array());

        return parent::disable();
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
    public function isApproved()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isApproved', array());

        return parent::isApproved();
    }

    /**
     * {@inheritDoc}
     */
    public function approve()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'approve', array());

        return parent::approve();
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
    public function setPrivateKey($privateKey)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPrivateKey', array($privateKey));

        return parent::setPrivateKey($privateKey);
    }

    /**
     * {@inheritDoc}
     */
    public function getPrivateKey()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPrivateKey', array());

        return parent::getPrivateKey();
    }

    /**
     * {@inheritDoc}
     */
    public function setShopUrl($url)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setShopUrl', array($url));

        return parent::setShopUrl($url);
    }

    /**
     * {@inheritDoc}
     */
    public function getShopUrl()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getShopUrl', array());

        return parent::getShopUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function setWebUrl($url)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setWebUrl', array($url));

        return parent::setWebUrl($url);
    }

    /**
     * {@inheritDoc}
     */
    public function getWebUrl()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getWebUrl', array());

        return parent::getWebUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function setFullSet($bool)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFullSet', array($bool));

        return parent::setFullSet($bool);
    }

    /**
     * {@inheritDoc}
     */
    public function isFullSet()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isFullSet', array());

        return parent::isFullSet();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreatedByAdmin($bool)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCreatedByAdmin', array($bool));

        return parent::setCreatedByAdmin($bool);
    }

    /**
     * {@inheritDoc}
     */
    public function isCreatedByAdmin()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isCreatedByAdmin', array());

        return parent::isCreatedByAdmin();
    }

    /**
     * {@inheritDoc}
     */
    public function suspend()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'suspend', array());

        return parent::suspend();
    }

    /**
     * {@inheritDoc}
     */
    public function resume()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'resume', array());

        return parent::resume();
    }

    /**
     * {@inheritDoc}
     */
    public function isSuspended()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isSuspended', array());

        return parent::isSuspended();
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
    public function recover()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'recover', array());

        return parent::recover();
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
    public function setBindShop($bool)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBindShop', array($bool));

        return parent::setBindShop($bool);
    }

    /**
     * {@inheritDoc}
     */
    public function isBindShop()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isBindShop', array());

        return parent::isBindShop();
    }

}