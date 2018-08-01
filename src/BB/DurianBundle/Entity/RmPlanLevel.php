<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 記錄刪除使用者計畫所刪除的會員層級
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name = "rm_plan_level",
 *     indexes = {@ORM\Index(name = "idx_rm_plan_level_level_id", columns = {"level_id"})}
 * )
 */
class RmPlanLevel
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
     * 計畫編號
     *
     * @var integer
     *
     * @ORM\Column(name = "plan_id", type = "integer")
     */
    private $planId;

    /**
     * 層級編號
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer")
     */
    private $levelId;

    /**
     * 層級名稱
     *
     * @var string
     *
     * @ORM\Column(name = "level_alias", type = "string", length = 50, options = {"default" = ""})
     */
    private $levelAlias;

    /**
     * 新增資料
     *
     * @param integer $planId     計畫編號
     * @param integer $levelId    層級編號
     * @param string  $levelAlias 層級名稱
     */
    public function __construct($planId, $levelId, $levelAlias)
    {
        $this->planId = $planId;
        $this->levelId = $levelId;
        $this->levelAlias = $levelAlias;
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
     * 回傳計畫編號
     *
     * @return integer
     */
    public function getPlanId()
    {
        return $this->planId;
    }

    /**
     * 回傳層級編號
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 回傳層級名稱
     *
     * @return string
     */
    public function getLevelAlias()
    {
        return $this->levelAlias;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'level_id' => $this->levelId,
            'level_alias' => $this->levelAlias
        ];
    }
}
