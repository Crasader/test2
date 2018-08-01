<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\RemitEntry;

/**
 * 自動認款相關資料
 *
 * @ORM\Entity
 * @ORM\Table(name = "remit_auto_confirm")
 */
class RemitAutoConfirm
{
    /**
     * 公司入款記錄
     *
     * @var RemitEntry
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "RemitEntry")
     * @ORM\JoinColumn(
     *      name = "remit_entry_id",
     *      referencedColumnName = "id"
     * )
     */
    private $remitEntry;

    /**
     * 自動認款紀錄
     *
     * @var string
     *
     * @ORM\Column(name = "auto_confirm_id", type = "string", length = 64)
     */
    private $autoConfirmId;

    /**
     * @param RemitEntry $remitEntry 公司入款記錄
     * @param string $autoConfirmId 自動認款 id
     */
    public function __construct(RemitEntry $remitEntry, $autoConfirmId)
    {
        $this->remitEntry = $remitEntry;
        $this->autoConfirmId = $autoConfirmId;
    }

    /**
     * 回傳公司入款記錄
     *
     * @return RemitEntry
     */
    public function getRemitEntry()
    {
        return $this->remitEntry;
    }

    /**
     * 回傳自動認款 id
     *
     * @return string
     */
    public function getAutoConfirmId()
    {
        return $this->autoConfirmId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'remit_entry_id' => $this->getRemitEntry()->getId(),
            'auto_confirm_id' => $this->getAutoConfirmId(),
        ];
    }
}
