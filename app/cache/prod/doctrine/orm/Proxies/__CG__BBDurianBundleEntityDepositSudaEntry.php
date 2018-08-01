<?php

namespace Proxies\__CG__\BB\DurianBundle\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class DepositSudaEntry extends \BB\DurianBundle\Entity\DepositSudaEntry implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'seqId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'userId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'merchantNumber', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'orderId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'code', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'alias', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'amount', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'offerDeposit', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'offerOther', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'bankInfoId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'recipient', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'account', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'fee', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'merchantSudaId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'createdAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'checkedUsername', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'confirmAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'confirm', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'cancel', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'memo');
        }

        return array('__isInitialized__', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'id', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'seqId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'userId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'domain', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'merchantNumber', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'orderId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'code', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'alias', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'amount', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'offerDeposit', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'offerOther', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'bankInfoId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'recipient', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'account', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'fee', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'merchantSudaId', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'createdAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'checkedUsername', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'confirmAt', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'confirm', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'cancel', '' . "\0" . 'BB\\DurianBundle\\Entity\\DepositSudaEntry' . "\0" . 'memo');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (DepositSudaEntry $proxy) {
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
    public function getSeqId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSeqId', array());

        return parent::getSeqId();
    }

    /**
     * {@inheritDoc}
     */
    public function setSeqId($seqId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSeqId', array($seqId));

        return parent::setSeqId($seqId);
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
    public function setUserId($userId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUserId', array($userId));

        return parent::setUserId($userId);
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
    public function getMerchantNumber()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getMerchantNumber', array());

        return parent::getMerchantNumber();
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantNumber($merchantNumber)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setMerchantNumber', array($merchantNumber));

        return parent::setMerchantNumber($merchantNumber);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrderId', array());

        return parent::getOrderId();
    }

    /**
     * {@inheritDoc}
     */
    public function setOrderId($orderId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOrderId', array($orderId));

        return parent::setOrderId($orderId);
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
    public function setCode($code)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCode', array($code));

        return parent::setCode($code);
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
    public function setAlias($alias)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAlias', array($alias));

        return parent::setAlias($alias);
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
    public function setAmount($amount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAmount', array($amount));

        return parent::setAmount($amount);
    }

    /**
     * {@inheritDoc}
     */
    public function getOfferDeposit()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOfferDeposit', array());

        return parent::getOfferDeposit();
    }

    /**
     * {@inheritDoc}
     */
    public function setOfferDeposit($offerDeposit)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOfferDeposit', array($offerDeposit));

        return parent::setOfferDeposit($offerDeposit);
    }

    /**
     * {@inheritDoc}
     */
    public function getOfferOther()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOfferOther', array());

        return parent::getOfferOther();
    }

    /**
     * {@inheritDoc}
     */
    public function setOfferOther($offerOther)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOfferOther', array($offerOther));

        return parent::setOfferOther($offerOther);
    }

    /**
     * {@inheritDoc}
     */
    public function getBankInfoId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBankInfoId', array());

        return parent::getBankInfoId();
    }

    /**
     * {@inheritDoc}
     */
    public function setBankInfoId($bankInfoId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBankInfoId', array($bankInfoId));

        return parent::setBankInfoId($bankInfoId);
    }

    /**
     * {@inheritDoc}
     */
    public function getRecipient()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRecipient', array());

        return parent::getRecipient();
    }

    /**
     * {@inheritDoc}
     */
    public function setRecipient($recipient)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRecipient', array($recipient));

        return parent::setRecipient($recipient);
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
    public function getFee()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFee', array());

        return parent::getFee();
    }

    /**
     * {@inheritDoc}
     */
    public function setFee($fee)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFee', array($fee));

        return parent::setFee($fee);
    }

    /**
     * {@inheritDoc}
     */
    public function getMerchantSudaId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getMerchantSudaId', array());

        return parent::getMerchantSudaId();
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantSudaId($merchantSudaId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setMerchantSudaId', array($merchantSudaId));

        return parent::setMerchantSudaId($merchantSudaId);
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
    public function setCreatedAt($createdAt)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCreatedAt', array($createdAt));

        return parent::setCreatedAt($createdAt);
    }

    /**
     * {@inheritDoc}
     */
    public function getCheckedUsername()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCheckedUsername', array());

        return parent::getCheckedUsername();
    }

    /**
     * {@inheritDoc}
     */
    public function setCheckedUsername($checkedUsername)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCheckedUsername', array($checkedUsername));

        return parent::setCheckedUsername($checkedUsername);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfirmAt()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getConfirmAt', array());

        return parent::getConfirmAt();
    }

    /**
     * {@inheritDoc}
     */
    public function confirm()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'confirm', array());

        return parent::confirm();
    }

    /**
     * {@inheritDoc}
     */
    public function unconfirm()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'unconfirm', array());

        return parent::unconfirm();
    }

    /**
     * {@inheritDoc}
     */
    public function isConfirm()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isConfirm', array());

        return parent::isConfirm();
    }

    /**
     * {@inheritDoc}
     */
    public function cancel()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'cancel', array());

        return parent::cancel();
    }

    /**
     * {@inheritDoc}
     */
    public function uncancel()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'uncancel', array());

        return parent::uncancel();
    }

    /**
     * {@inheritDoc}
     */
    public function isCancel()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isCancel', array());

        return parent::isCancel();
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
    public function setMemo($memo)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setMemo', array($memo));

        return parent::setMemo($memo);
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
