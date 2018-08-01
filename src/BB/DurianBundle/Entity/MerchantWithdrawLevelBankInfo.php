<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\BankInfo;

/**
 * 出款商家層級支援銀行資料
 *
 * @ORM\Entity
 * @ORM\Table(name = "merchant_withdraw_level_bank_info")
 */
class MerchantWithdrawLevelBankInfo
{
    /**
     * 出款商家
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "merchant_withdraw_id", type = "integer", options = {"unsigned" = true})
     */
    private $merchantWithdrawId;

    /**
     * 層級
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 銀行資料
     *
     * @var BankInfo
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "BankInfo")
     * @ORM\JoinColumn(
     *      name = "bank_info_id",
     *      referencedColumnName = "id",
     *      nullable = false)
     */
    private $bankInfo;

    /**
     * MerchantWithdrawLevelBankInfo constructor
     *
     * @param integer $merchantWithdrawId 出款商家id
     * @param integer $levelId 層級id
     * @param BankInfo $bankInfo 銀行資料
     */
    public function __construct($merchantWithdrawId, $levelId, BankInfo $bankInfo)
    {
        $this->merchantWithdrawId = $merchantWithdrawId;
        $this->levelId = $levelId;
        $this->bankInfo = $bankInfo;
    }

    /**
     * 回傳出款商家
     *
     * @return integer
     */
    public function getMerchantWithdrawId()
    {
        return $this->merchantWithdrawId;
    }

    /**
     * 回傳層級
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 回傳銀行資料
     *
     * @return BankInfo
     */
    public function getBankInfo()
    {
        return $this->bankInfo;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'merchant_withdraw_id' => $this->getMerchantWithdrawId(),
            'level_id' => $this->getLevelId(),
            'bank_info' => $this->getBankInfo()->getId()
        ];
    }
}
