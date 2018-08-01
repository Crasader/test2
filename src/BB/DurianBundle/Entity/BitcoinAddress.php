<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 比特幣入款位址 - 廳主針對每個會員建立入款位址
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name = "bitcoin_address",
 *     uniqueConstraints = {@ORM\UniqueConstraint(
 *         name = "uni_user_id",
 *         columns = {"user_id"})
 *     },
 *     indexes = {
 *         @ORM\Index(name = "idx_bitcoin_address_user_id", columns = {"user_id"}),
 *     }
 * )
 */
class BitcoinAddress
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
     * 對應的使用者
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "bigint")
     */
    private $userId;

    /**
     * 對應的比特幣錢包
     *
     * @var integer
     *
     * @ORM\Column(name = "wallet_id", type = "integer")
     */
    private $walletId;

    /**
     * 比特幣入款帳號 - 比特幣的公鑰: xpub
     *
     * @var string
     *
     * @ORM\Column(name = "account", type = "string", length = 256)
     */
    private $account;

    /**
     * 比特幣入款位址
     *
     * @var string
     *
     * @ORM\Column(name = "address", type = "string", length = 64)
     */
    private $address;

    /**
     * 新增比特幣帳號資訊
     *
     * @param integer $userId   使用者
     * @param integer $walletId 比特幣錢包
     * @param string  $account  入款帳號
     * @param string  $address  入款位址
     */
    public function __construct($userId, $walletId, $account, $address)
    {
        $this->userId = $userId;
        $this->walletId = $walletId;
        $this->account = $account;
        $this->address = $address;
    }

    /**
     * @return integer
     */
    function getId()
    {
        return $this->id;
    }

    /**
     * 取得使用者
     *
     * @return string
     */
    function getUserId()
    {
        return $this->userId;
    }

    /**
     * 取得錢包
     *
     * @return string
     */
    function getWalletId()
    {
        return $this->walletId;
    }

    /**
     * 設定入款帳號
     *
     * @param string $account
     * @return BitcoinAddress
     */
    function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * 取得入款帳號
     *
     * @return string
     */
    function getAccount()
    {
        return $this->account;
    }

    /**
     * 設定入款位址
     *
     * @param string $address
     * @return BitcoinAddress
     */
    function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * 取得入款位址
     *
     * @return string
     */
    function getAddress()
    {
        return $this->address;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'wallet_id' => $this->getWalletId(),
            'address' => $this->getAddress(),
        ];
    }
}
