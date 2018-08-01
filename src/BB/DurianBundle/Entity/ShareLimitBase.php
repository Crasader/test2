<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 通用佔成上下限設定
 * 在新增、修改、刪除前，請使用Share\Validator來檢查佔成的正確性。
 *
 * @ORM\MappedSuperclass
 */
class ShareLimitBase
{
    /**
     * 群組編號
     *
     * @var integer
     *
     * @ORM\Column(name = "group_num", type = "integer")
     */
    private $groupNum;

    /**
     * 佔成(含下層)上限
     * 單位是%
     *
     * @var float
     *
     * @ORM\Column(name = "upper", type = "decimal", precision = 4, scale = 1)
     */
    private $upper;

    /**
     * 佔成(含下層)下限
     * 單位是%
     *
     * @var float
     *
     * @ORM\Column(name = "lower", type = "decimal", precision = 4, scale = 1)
     */
    private $lower;

    /**
     * 上層的佔成(不含下層)上限
     * 單位是%
     *
     * @var float
     *
     * @ORM\Column(name = "parent_upper", type = "decimal", precision = 4, scale = 1)
     */
    private $parentUpper;

    /**
     * 上層的佔成(不含下層)下限
     * 單位是%
     *
     * @var float
     *
     * @ORM\Column(name = "parent_lower", type = "decimal", precision = 4, scale = 1)
     */
    private $parentLower;

    /**
     * 全部下層 MIN(parent_upper+lower)
     *
     * @var float
     *
     * @ORM\Column(name = "min1", type = "decimal", precision = 4, scale = 1)
     */
    private $min1;

    /**
     * 全部下層 MAX(parent_upper)
     *
     * @var float
     *
     * @ORM\Column(name = "max1", type = "decimal", precision = 4, scale = 1)
     */
    private $max1;

    /**
     * 全部下層 MAX(parent_lower+upper)
     *
     * @var float
     *
     * @ORM\Column(name = "max2", type = "decimal", precision = 4, scale = 1)
     */
    private $max2;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 標記upper, lower, parentUpper, parentLower有沒有更動
     * flush()後會恢復成false
     *
     * @var bool
     */
    private $changed;

    /**
     * @param integer $groupNum 群組編號
     */
    public function __construct($groupNum)
    {
        $this->groupNum = $groupNum;

        $this->upper = 100;
        $this->lower = 0;
        $this->parentUpper = 100;
        $this->parentLower = 0;

        $this->min1 = 200;
        $this->max1 = 0;
        $this->max2 = 0;

        $this->doStaffOnLimitChange();
    }

    /**
     * 回傳群組編號
     *
     * @return integer
     */
    public function getGroupNum()
    {
        return $this->groupNum;
    }

    /**
     * 設定佔成(含下層)上限
     * 單位是%
     *
     * @param float $value
     * @return ShareLimitBase
     */
    public function setUpper($value)
    {
        if ($this->upper === $value) {
            return $this;
        }

        $this->upper = $value;

        $this->doStaffOnLimitChange();

        return $this;
    }

    /**
     * 回傳佔成(含下層)上限
     * 單位是%
     *
     * @return float
     */
    public function getUpper()
    {
        return $this->upper;
    }

    /**
     * 設定佔成(含下層)下限
     * 單位是%
     *
     * @param float $value
     * @return ShareLimitBase
     */
    public function setLower($value)
    {
        if ($this->lower === $value) {
            return $this;
        }

        $this->lower = $value;

        $this->doStaffOnLimitChange();

        return $this;
    }

    /**
     * 回傳佔成(含下層)下限
     * 單位是%
     *
     * @return float
     */
    public function getLower()
    {
        return $this->lower;
    }

    /**
     * 設定上層的佔成(不含下層)上限
     * 單位是%
     *
     * @param float $value
     * @return ShareLimitBase
     */
    public function setParentUpper($value)
    {
        if ($this->parentUpper === $value) {
            return $this;
        }

        $this->parentUpper = $value;

        $this->doStaffOnLimitChange();

        return $this;
    }

    /**
     * 回傳上層的佔成(不含下層)上限
     * 單位是%
     *
     * @return float
     */
    public function getParentUpper()
    {
        return $this->parentUpper;
    }

    /**
     * 設定上層的佔成(不含下層)下限
     * 單位是%
     *
     * @param float $value
     * @return ShareLimitBase
     */
    public function setParentLower($value)
    {
        if ($this->parentLower === $value) {
            return $this;
        }

        $this->parentLower = $value;

        $this->doStaffOnLimitChange();

        return $this;
    }

    /**
     * 回傳上層的佔成(不含下層)下限
     * 單位是%
     *
     * @return float
     */
    public function getParentLower()
    {
        return $this->parentLower;
    }

    /**
     * 設定全部下層 MIN(parent_upper+lower)
     *
     * @param float $value
     * @return ShareLimitBase
     */
    public function setMin1($value)
    {
        $this->min1 = $value;

        return $this;
    }

    /**
     * 回傳全部下層 MIN(parent_upper+lower)
     *
     * @return float
     */
    public function getMin1()
    {
        return $this->min1;
    }

    /**
     * 設定全部下層 MAX(parent_upper)
     *
     * @param float $value
     * @return ShareLimitBase
     */
    public function setMax1($value)
    {
        $this->max1 = $value;

        return $this;
    }

    /**
     * 回傳設定全部下層 MAX(parent_upper)
     *
     * @return float
     */
    public function getMax1()
    {
        return $this->max1;
    }

    /**
     * 設定全部下層 MAX(parent_lower+upper)
     *
     * @param float $value
     * @return ShareLimitBase
     */
    public function setMax2($value)
    {
        $this->max2 = $value;

        return $this;
    }

    /**
     * 回傳全部下層 MAX(parent_lower+upper)
     *
     * @return float
     */
    public function getMax2()
    {
        return $this->max2;
    }

    /**
     * 回傳佔成是否有修改
     * 在修改upper, lower, parentUpper, parentLower時會變成已修改
     * flush()以後會恢復未修改狀態
     *
     * @return bool
     */
    public function isChanged()
    {
        return $this->changed;
    }

    /**
     * 重置佔成是否修改標記
     *
     * @return ShareLimitBase
     */
    public function resetChanged()
    {
        $this->changed = false;

        return $this;
    }

    /**
     * 回傳是否有上層ShareLimit
     *
     * @return ShareLimitBase
     */
    public function hasParent()
    {
        return $this->getUser()->hasParent();
    }

    /**
     * 在上下限改變時處理一些事
     */
    private function doStaffOnLimitChange()
    {
        $this->changed = true;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'           => $this->getId(),
            'user_id'      => $this->getUser()->getId(),
            'group'        => $this->getGroupNum(),
            /**
             * 佔成+0這個動作可以讓.0不顯示出來
             */
            'upper'        => $this->getUpper() + 0,
            'lower'        => $this->getLower() + 0,
            'parent_upper' => $this->getParentUpper() + 0,
            'parent_lower' => $this->getParentLower() + 0,
        );
    }
}
