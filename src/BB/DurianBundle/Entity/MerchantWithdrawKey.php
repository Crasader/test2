<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\MerchantWithdraw;

/**
 * 出款商家金鑰檔案
 *
 * @ORM\Entity
 * @ORM\Table(name = "merchant_withdraw_key")
 */
class MerchantWithdrawKey
{
    /**
     * 金鑰檔案內容最大字數
     */
    const MAX_FILE_LENGTH = 4096;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 出款商家
     *
     * @var MerchantWithdraw
     *
     * @ORM\ManyToOne(targetEntity = "MerchantWithdraw")
     * @ORM\JoinColumn(
     *     name = "merchant_withdraw_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $merchantWithdraw;

    /**
     * 金鑰類別
     *
     * @var string
     *
     * @ORM\Column(name = "key_type", type = "string", length = 20)
     */
    private $keyType;

    /**
     * 金鑰檔案內容
     *
     * @var string
     *
     * @ORM\Column(name = "file_content", type = "string", length = 4096)
     */
    private $fileContent;

    /**
     * @param MerchantWithdraw $merchantWithdraw 出款商家
     * @param string $keyType 金鑰類別
     * @param string $fileContent 檔案內容
     */
    public function __construct(MerchantWithdraw $merchantWithdraw, $keyType, $fileContent)
    {
        $this->merchantWithdraw = $merchantWithdraw;
        $this->keyType = $keyType;
        $this->fileContent = $fileContent;
    }

    /**
     * 回傳管端商家
     *
     * @return MerchantWithdraw
     */
    public function getMerchantWithdraw()
    {
        return $this->merchantWithdraw;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳金鑰類型
     *
     * @return string
     */
    public function getKeyType()
    {
        return $this->keyType;
    }

    /**
     * 回傳金鑰檔案內容
     *
     * @return string
     */
    public function getFileContent()
    {
        return $this->fileContent;
    }

    /**
     * 修改金鑰檔案內容
     *
     * @param string
     * @return MerchantWithdrawKey
     */
    public function setFileContent($fileContent)
    {
        $this->fileContent = $fileContent;

        return $this;
    }
}
