<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Merchant;

/**
 * 商家金鑰檔案
 *
 * @ORM\Entity
 * @ORM\Table(name = "merchant_key")
 */
class MerchantKey
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
     * 商家
     *
     * @var Merchant
     *
     * @ORM\ManyToOne(targetEntity = "Merchant")
     * @ORM\JoinColumn(
     *     name = "merchant_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $merchant;

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
     * @param Merchant $merchant    商家
     * @param string   $keyType     金鑰類別
     * @param string   $fileContent 檔案內容
     */
    public function __construct(Merchant $merchant, $keyType, $fileContent)
    {
        $this->merchant = $merchant;
        $this->keyType = $keyType;
        $this->fileContent = $fileContent;
    }

    /**
     * 回傳商家
     *
     * @return Merchant
     */
    public function getMerchant()
    {
        return $this->merchant;
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
     * @return MerchantKey
     */
    public function setFileContent($fileContent)
    {
        $this->fileContent = $fileContent;

        return $this;
    }
}
