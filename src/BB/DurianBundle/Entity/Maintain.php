<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 遊戲維護資訊
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MaintainRepository")
 * @ORM\Table(name = "maintain")
 */
class Maintain
{
    /**
     * 遊戲代碼
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "code", type = "smallint", options = {"unsigned" = true})
     */
    private $code;

    /**
     * 維護開始時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "begin_at", type = "datetime")
     */
    private $beginAt;

    /**
     * 維護結束時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "end_at", type = "datetime")
     */
    private $endAt;

    /**
     * 維護內容變動時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "modified_at", type = "datetime")
     */
    private $modifiedAt;

    /**
     * 維護訊息
     *
     * @var string
     *
     * @ORM\Column(name = "msg", type = "string", length = 100)
     */
    private $msg;

    /**
     * 操作者
     *
     * @var string
     *
     * @ORM\Column(name = "operator", type = "string", length = 30)
     *
     */
    private $operator;

    /**
     * constructor
     *
     * @param integer    $code
     * @param \DateTime  $startAt
     * @param \DateTime  $endAt
     */
    public function __construct($code, \DateTime $beginAt, \DateTime $endAt)
    {
        $this->code       = $code;
        $this->beginAt    = $beginAt;
        $this->endAt      = $endAt;
        $this->modifiedAt = new \DateTime();
        $this->msg        = '';
        $this->operator   = '';
    }

    /**
     * 取得遊戲代碼
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 設定維護開始時間
     *
     * @param \DateTime $beginAt
     * @return Maintain
     */
    public function setBeginAt(\Datetime $beginAt)
    {
        $this->beginAt = $beginAt;

        return $this;
    }

    /**
     * 取得維護開始時間
     *
     * @return \Datetime
     */
    public function getBeginAt()
    {
        return $this->beginAt;
    }

    /**
     * 設定維護結束時間
     *
     * @param \DateTime $endAt
     * @return Maintain
     */
    public function setEndAt(\Datetime $endAt)
    {
        $this->endAt = $endAt;

        return $this;
    }

    /**
     * 取得維護結束時間
     *
     * @return \Datetime
     */
    public function getEndAt()
    {
        return $this->endAt;
    }

    /**
     * 設定維護內容變動時間
     *
     * @param \DateTime $time
     *
     * @return Maintain
     */
    public function setModifiedAt(\DateTime $time)
    {
        $this->modifiedAt = $time;

        return $this;
    }

    /**
     * 取得維護內容變動時間
     *
     * @return \Datetime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * 設定維護訊息
     *
     * @param string $msg
     *
     * @return Maintain
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;

        return $this;
    }

    /**
     * 取得維護訊息
     *
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * 設定操作者
     *
     * @param string $operator
     *
     * @return Maintain
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * 取得操作者
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'code'        => $this->getCode(),
            'begin_at'    => $this->getBeginAt()->format(\DateTime::ISO8601),
            'end_at'      => $this->getEndAt()->format(\DateTime::ISO8601),
            'modified_at' => $this->getModifiedAt()->format(\DateTime::ISO8601),
            'msg'         => $this->getMsg(),
            'operator'    => $this->getOperator()
        );
    }
}
