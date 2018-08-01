<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 假現金使用者api轉入轉出紀錄
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name = "user_has_api_transfer_in_out"
 * )
 */
class UserHasApiTransferInOut
{
    /**
     * api轉入轉出紀錄對應的假現金使用者ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "bigint", options = {"unsigned" = true})
     */
    private $userId;

    /**
     * 是否api轉入
     *
     * @var boolean
     *
     * @ORM\Column(name = "api_transfer_in", type = "boolean")
     */
    private $apiTransferIn;

    /**
     * 是否api轉出
     *
     * @var boolean
     *
     * @ORM\Column(name = "api_transfer_out", type = "boolean")
     */
    private $apiTransferOut;

    /**
     * 建構子
     *
     * @param integer $user           使用者id
     * @param boolean $apiTransferIn  是否api轉入
     * @param boolean $apiTransferOut 是否api轉出
     */
    public function __construct($userId, $apiTransferIn, $apiTransferOut)
    {
        $this->userId = $userId;
        $this->apiTransferIn = $apiTransferIn;
        $this->apiTransferOut = $apiTransferOut;
    }

    /**
     * 取得使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 取得api轉入
     *
     * @return boolean
     */
    public function isApiTransferIn()
    {
        return $this->apiTransferIn;
    }

    /**
     * 設定api轉入
     *
     * @param boolean $apiTransferIn
     * @return UserHasApiTransferInOut
     */
    public function setApiTransferIn($apiTransferIn)
    {
        $this->apiTransferIn = $apiTransferIn;

        return $this;
    }

    /**
     * 取得api轉出
     *
     * @return boolean
     */
    public function isApiTransferOut()
    {
        return $this->apiTransferOut;
    }

    /**
     * 設定api轉出
     *
     * @param boolean $apiTransferOut
     * @return UserHasApiTransferInOut
     */
    public function setApiTransferOut($apiTransferOut)
    {
        $this->apiTransferOut = $apiTransferOut;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'user_id' => $this->getUserId(),
            'api_transfer_in' => $this->isApiTransferIn(),
            'api_transfer_out' => $this->isApiTransferOut()
        ];
    }
}
