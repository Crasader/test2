<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 比特幣錢包
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name = "bitcoin_wallet",
 *     indexes = {
 *         @ORM\Index(name = "idx_bitcoin_wallet_domain", columns = {"domain"}),
 *     }
 * )
 */
class BitcoinWallet
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 錢包帳號, BLOCKCHAIN內的Wallet ID
     *
     * @var string
     *
     * @ORM\Column(name = "wallet_code", type = "string", length = 64)
     */
    private $walletCode;

    /**
     * 錢包密碼
     *
     * @var string
     *
     * @ORM\Column(name = "password", type = "string", length = 64)
     */
    private $password;

    /**
     * 錢包密碼, 出款用
     *
     * @var string
     *
     * @ORM\Column(name = "second_password", type = "string", length = 64, nullable = true)
     */
    private $secondPassword;

    /**
     * BLOCKCHAIN的api碼
     *
     * @var string
     *
     * @ORM\Column(name = "api_code", type = "string", length = 64)
     */
    private $apiCode;

    /**
     * 出款公鑰
     *
     * @var string
     *
     * @ORM\Column(name = "xpub", type = "string", length = 256, nullable = true)
     */
    private $xpub;

    /**
     * 比特幣出款手續費率
     *
     * @var float
     *
     * @ORM\Column(name = "fee_per_byte", type = "decimal", precision = 16, scale = 4, options = {"default" = 0})
     */
    private $feePerByte;

    /**
     * 新增廳主比特幣資訊
     *
     * @param integer $domain         廳
     * @param string  $walletId       錢包
     * @param string  $password       密碼
     * @param string  $secondPassword 第二密碼
     * @param string  $apiCode        api碼
     */
    public function __construct(
        $domain,
        $walletCode,
        $password,
        $apiCode
    ) {
        $this->domain = $domain;
        $this->walletCode = $walletCode;
        $this->password = $password;
        $this->apiCode = $apiCode;
        $this->feePerByte = 0;
    }

    /**
     * @return integer
     */
    function getId()
    {
        return $this->id;
    }

    /**
     * 取得廳
     *
     * @return integer
     */
    function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定錢包帳號
     *
     * @param string $walletCode
     * @return BitcoinWallet
     */
    function setWalletCode($walletCode)
    {
        $this->walletCode = $walletCode;

        return $this;
    }

    /**
     * 取得錢包帳號
     *
     * @return string
     */
    function getWalletCode()
    {
        return $this->walletCode;
    }

    /**
     * 設定錢包密碼
     *
     * @param string $password
     * @return BitcoinWallet
     */
    function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * 取得錢包密碼
     *
     * @return string
     */
    function getPassword()
    {
        return $this->password;
    }

    /**
     * 設定錢包第二密碼
     *
     * @param string $secondPassword
     * @return BitcoinWallet
     */
    function setSecondPassword($secondPassword)
    {
        $this->secondPassword = $secondPassword;

        return $this;
    }

    /**
     * 取得錢包第二密碼
     *
     * @return string
     */
    function getSecondPassword()
    {
        return $this->secondPassword;
    }

    /**
     * 設定api碼
     *
     * @param string $apiCode
     * @return BitcoinWallet
     */
    function setApiCode($apiCode)
    {
        $this->apiCode = $apiCode;

        return $this;
    }

    /**
     * 取得api碼
     *
     * @return string
     */
    function getApiCode()
    {
        return $this->apiCode;
    }

    /**
     * 設定出款公鑰
     *
     * @param string $xpub
     * @return BitcoinWallet
     */
    function setXpub($xpub)
    {
        $this->xpub = $xpub;

        return $this;
    }

    /**
     * 取得出款公鑰
     *
     * @return string
     */
    function getXpub()
    {
        return $this->xpub;
    }

    /**
     * 設定比特幣出款手續費率
     *
     * @param float $feePerByte
     * @return BitcoinWallet
     */
    function setFeePerByte($feePerByte)
    {
        $this->feePerByte = $feePerByte;

        return $this;
    }

    /**
     * 取得比特幣出款手續費率
     *
     * @return float
     */
    function getFeePerByte()
    {
        return $this->feePerByte;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'domain' => $this->getDomain(),
            'wallet_code' => $this->getWalletCode(),
            'api_code' => $this->getApiCode(),
            'xpub' => $this->getXpub(),
            'fee_per_byte' => $this->getFeePerByte(),
        ];
    }
}
