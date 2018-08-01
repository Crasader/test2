<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\RemitAccount;

/**
 * 出入款帳號Qrcode
 *
 * @ORM\Entity
 * @ORM\Table(name = "remit_account_qrcode")
 */
class RemitAccountQrcode
{
    /**
     * 出入款帳號ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "remit_account_id", type = "integer", options = {"unsigned" = true})
     */
    private $remitAccountId;

    /**
     * Qrcode
     *
     * @var text
     *
     * @ORM\Column(name = "qrcode", type = "text")
     */
    private $qrcode;

    /**
     * @param RemitAccount $remitAccount 出入款帳號
     * @param text $qrcode
     */
    public function __construct(RemitAccount $remitAccount, $qrcode)
    {
        $this->remitAccountId = $remitAccount->getId();
        $this->qrcode = $qrcode;
    }

    /**
     * 回傳出入款帳號ID
     *
     * @return integer
     */
    public function getRemitAccountId()
    {
        return $this->remitAccountId;
    }

    /**
     * 回傳Qrcode
     *
     * @return text
     */
    public function getQrcode()
    {
        return $this->qrcode;
    }

    /**
     * 設定Qrcode
     *
     * @param text $qrcode
     * @return RemitAccountQrcode
     */
    public function setQrcode($qrcode)
    {
        $this->qrcode = $qrcode;

        return $this;
    }
}
