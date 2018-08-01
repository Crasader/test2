<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 紀錄現行及歷史快開額度差異的資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashFakeEntryDiffRepository")
 * @ORM\Table(name="cash_fake_entry_diff")
 */
class CashFakeEntryDiff
{
    /**
     * 交易明細ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint")
     */
    private $id;

    /**
     * 檢查時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "check_time", type = "datetime")
     */
    private $checkTime;

    public function __construct()
    {
        $this->checkTime = new \DateTime('now');
    }

    /**
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳檢查日期
     *
     * @return \DateTime
     */
    public function getCheckTime()
    {
        return $this->checkTime;
    }

    /**
     * 設定id
     *
     * @param integer $id
     * @return CashFakeEntryDiff
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'         => $this->id,
            'check_time' => $this->checkTime->format(\DateTime::ISO8601)
        );
    }
}
