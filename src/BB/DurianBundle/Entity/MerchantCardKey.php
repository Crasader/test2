<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\MerchantCard;

/**
 * 租卡商家金鑰檔案
 *
 * @ORM\Entity
 * @ORM\Table(name = "merchant_card_key")
 */
class MerchantCardKey
{
    /**
     * 金鑰檔案內容最大字數
     */
    const MAX_FILE_LENGTH = 4096;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 租卡商家
     *
     * @var MerchantCard
     *
     * @ORM\ManyToOne(targetEntity = "MerchantCard")
     * @ORM\JoinColumn(
     *     name = "merchant_card_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $merchantCard;

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
     * @param MerchantCard $merchantCard 租卡商家
     * @param string $keyType 金鑰類別
     * @param string $fileContent 檔案內容
     */
    public function __construct(MerchantCard $merchantCard, $keyType, $fileContent)
    {
        $this->merchantCard = $merchantCard;
        $this->keyType = $keyType;
        $this->fileContent = $fileContent;
    }

    /**
     * 回傳租卡商家
     *
     * @return MerchantCard
     */
    public function getMerchantCard()
    {
        return $this->merchantCard;
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
     * @return MerchantCardKey
     */
    public function setFileContent($fileContent)
    {
        $this->fileContent = $fileContent;

        return $this;
    }
}
