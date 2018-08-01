<?php

namespace BB\DurianBundle;

class ParameterHandler
{
    /**
     * 處理OrderBy，將$sort的內容作為key，$order的內容
     * 作為value組成新陣列$orderBy回傳
     *
     * Ex: sort = ['id', 'opcode', 'at'];
     *     order = ['asc', 'desc'];
     *     return ['id' => 'asc', 'opcode' => 'desc', 'at' => 'desc'];
     *
     * @param array|string $sort 排序欄位
     * @param array|string $order 排序方式
     * @return array
     */
    public function orderBy($sort = null, $order = null)
    {
        if (is_null($sort)) {
            return [];
        }

        $orderBy = [];
        $order = is_null($order) ? 'asc' : $order;
        $sortArray = is_array($sort) ? $sort : [$sort];

        $sortCount = count($sortArray);

        if (is_array($order)) {
            $order = array_pad($order, $sortCount, end($order));
        } else {
            $order = array_pad([], $sortCount, $order);
        }

        foreach ($sortArray as $key => $sort) {
            $sort = \Doctrine\Common\Util\Inflector::camelize($sort);
            $orderBy[$sort] = $order[$key];
        }

        return $orderBy;
    }

    /**
     * 處理時間參數為Y-m-d H:i:s格式
     *
     * @param string $time
     * @return null|string
     */
    public function datetimeToYmdHis($time)
    {
        /**
         * 檢查參數為null/空字串/空白字元，參考http://php.net/manual/en/function.empty.php
         * datetimeToInt()亦同
         */
        if (trim($time) == false) {
            return null;
        }

        $datetime = new \DateTime($time);
        $datetime->setTimezone(new \DateTimeZone('Asia/Taipei'));

        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * 處理時間參數為YmdHis格式
     *
     * @param string $time
     * @return null|string
     */
    public function datetimeToInt($time)
    {
        if (trim($time) == false) {
            return null;
        }

        $datetime = new \DateTime($time);
        $datetime->setTimezone(new \DateTimeZone('Asia/Taipei'));

        return $datetime->format('YmdHis');
    }

    /**
     * 過濾特殊字元
     *
     * @param string $text
     * @return string
     */
    public function filterSpecialChar($text)
    {
        return preg_replace('/[^\pL\pN\pP\pS\pZ\pM]/u', '', $text);
    }
}
