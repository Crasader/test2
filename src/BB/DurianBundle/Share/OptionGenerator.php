<?php

namespace BB\DurianBundle\Share;

use BB\DurianBundle\Entity\ShareLimitBase;

/**
 * 產生出佔成可選選項
 *
 * @author sliver
 */
class OptionGenerator
{
    /**
     * 回傳佔成(含下層)上限可選選項
     *
     * @param ShareLimitBase $shareLimit
     * @return array
     */
    public function getUpperOption($shareLimit)
    {
        $max = Validator::LIMIT_MAX;
        $min = Validator::LIMIT_MIN;

        if (!$shareLimit->hasParent()) {
            return $this->getRangeArray($max, $min);
        }

        $p = $shareLimit->getParent();

        // 兩值取小
        $max = ($max < $p->getUpper() ? $max : $p->getUpper());

        return $this->getRangeArray($max, $min);
    }

    /**
     * 回傳佔成(含下層)下限可選選項
     *
     * @param ShareLimitBase $shareLimit
     * @return array
     */
    public function getLowerOption($shareLimit)
    {
        $max = Validator::LIMIT_MAX;
        $min = Validator::LIMIT_MIN;

        if (!$shareLimit->hasParent()) {
            return $this->getRangeArray($max, $min);
        }

        $p = $shareLimit->getParent();

        // 兩值取小
        $max = ($max < $p->getUpper() ? $max : $p->getUpper());

        return $this->getRangeArray($max, $min);
    }

    /**
     * 回傳上層自身佔成上限可選選項
     *
     * @param ShareLimitBase $shareLimit
     * @return array
     */
    public function getParentUpperOption($shareLimit)
    {
        $max = Validator::LIMIT_MAX;
        $min = Validator::LIMIT_MIN;

        if (!$shareLimit->hasParent()) {
            return $this->getRangeArray($max, $min);
        }

        $p = $shareLimit->getParent();

        // 兩值取小
        $max = ($max < $p->getUpper() ? $max : $p->getUpper());

        return $this->getRangeArray($max, $min);
    }

    /**
     * 回傳上層自身佔成下限可選選項
     *
     * @param ShareLimitBase $shareLimit
     * @return array
     */
    public function getParentLowerOption($shareLimit)
    {
        $max = Validator::LIMIT_MAX;
        $min = Validator::LIMIT_MIN;

        if (!$shareLimit->hasParent()) {
            return $this->getRangeArray($max, $min);
        }

        $p = $shareLimit->getParent();

        // 兩值取小
        $max = ($max < $p->getUpper() ? $max : $p->getUpper());

        return $this->getRangeArray($max, $min);
    }

    /**
     * 輸入佔成可設定最大和最小值回傳可選選項
     * 80%以上每個選項相差1%，80&以下每個選項相差5%
     *
     * @param integer $max 可選最大值
     * @param integer $min 可選最小值
     *
     * @return array
     */
    private function getRangeArray($max, $min)
    {
        // 狀況一。80%以上每個選項都差 1% --目前邏輯這個情況不會發生
        /**
         * if ($max > 80 && $min > 80) {
         *     $option = range($max, $min, 1);
         * }
         */

        // 狀況二。分成超過80%和80%以下兩部分處理
        if ($max > 80 && $min <= 80) {
            $up80 = range($max, 81, 1);

            $low80 = range(80, $min, 5);

            $option = array_merge($up80, $low80);
        }

        // 狀況三。80%以下每個選項相差 5%
        if ($max <= 80 && $min <= 80) {
            $option = range($max, $min, 5);
        }

        return $option;
    }
}
