<?php
namespace BB\DurianBundle;

class Validator
{
    /**
     * 檢查編碼是否為UTF8
     *
     * @param string|array $checkParameter
     */
    public function validateEncode($checkParameter)
    {
        if (!is_array($checkParameter)) {
            $checkParameter = array($checkParameter);
        }

        foreach ($checkParameter as $value) {
            if (!mb_check_encoding($value, 'UTF-8')) {
                throw new \InvalidArgumentException('String must use utf-8 encoding', 150610002);
            }

            // utf-8 無法儲存 3bytes 以上的字元
            $len = mb_strlen($value);
            for ($i = 0; $i < $len; $i++) {
                $word = mb_substr($value, $i, 1);

                if (strlen($word) > 3) {
                    throw new \InvalidArgumentException('Illegal character', 150610007);
                }
            }
        }
    }

    /**
     * 檢查小數點後幾位在指定位數內
     *
     * @param float $number The number being validated
     * @param int $decimals Sets the number of decimal points
     * @throws \InvalidArgumentException
     */
    public function validateDecimal($number, $decimals = 0)
    {
        $digits = array();

        preg_match('/.*\.(.*)/', (string) $number, $digits);

        if ($number && filter_var($number, FILTER_VALIDATE_FLOAT) === false) {
            throw new \InvalidArgumentException('Invalid decimal', 150610006);
        }

        if (isset($digits[1])) {
            if (strlen($digits[1]) > $decimals) {
                throw new \RangeException('The decimal digit of amount exceeds limitation', 150610003);
            }
        }
    }

    /**
     * 檢查ref_id型態是否為int,且值在0~9223372036854775806之間
     *
     * @param integer $refId
     * @return boolean
     */
    public function validateRefId($refId)
    {
        // infobright bigint max value is 9223372036854775806
        $intOptions = array(
            'options' => array(
                'min_range' => 0,
                'max_range' => 9223372036854775806
            )
        );

        if (filter_var($refId, FILTER_VALIDATE_INT, $intOptions) === false) {
            return true;
        }

        return false;
    }

    /**
     * 驗證opcode是不是在有效範圍內
     *
     * @param integer $value
     * @return boolean
     */
    public function validateOpcode($value)
    {
        $intOptions = array(
            'options' => array(
                'min_range' => 1,
                'max_range' => 999999
            )
        );

        if (filter_var($value, FILTER_VALIDATE_INT, $intOptions) === false) {
            return false;
        }

        return true;
    }

    /**
     * 檢查是否為整數,$disallowNegative帶true時，則驗證是否為正整數
     *
     * @param mixed $value
     * @param boolean $disallowNegative 是否檢查為正數
     * @return boolean
     */
    public function isInt($value, $disallowNegative = false)
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return false;
        }

        if ($disallowNegative && $value < 0) {
            return false;
        }

        return true;
    }

    /**
     * 檢查是否為浮點數，$disallowNegative帶true時，則驗證是否為正浮點數
     *
     * @param mixed $value
     * @param boolean $disallowNegative 是否檢查為正浮點數
     * @return boolean
     */
    public function isFloat($value, $disallowNegative = false)
    {
        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            return false;
        }

        if ($disallowNegative && $value < 0) {
            return false;
        }

        if (strpos($value, ' ') !== false) {
            return false;
        }

        return true;
    }

    /**
     * 檢查ip格式
     *
     * @param string $ip
     * @return boolean
     */
    public function validateIp($ip)
    {
        if(filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return true;
    }

    /**
     * 檢查時間格式與區間是否正確
     *
     * @param string $date
     * @return boolean
     */
    public function validateDate($date)
    {
        // 去掉空白，檢查時間格式
        $dateParse = date_parse(trim($date));

        // 格式錯誤會有error_count，不存在日期(2月30)會有warning_count
        if ($dateParse['error_count'] > 0 || $dateParse['warning_count'] > 0) {
            return false;
        }

        // 只輸入部分數字會判斷為時間，日月年則為false，用來判斷輸入不完整的日期時間
        if (!$dateParse['year'] || !$dateParse['month'] || !$dateParse['day']) {
            return false;
        }

        return true;
    }

    /**
     * 驗證電話號碼 允許格式 ex:+11111111111或11111111111
     *
     * @param string $telephone
     */
    public function validateTelephone($telephone)
    {
        if (!preg_match('/^(\+\d)?\d*$/', $telephone)) {
            throw new \InvalidArgumentException('Invalid telephone', 150610001);
        }
    }

    /**
     * 檢查時間格式是否正確且成對帶入
     *
     * @param string $start
     * @param string $end
     * @return boolean
     */
    public function validateDateRange($start, $end)
    {
        if (!$this->validateDate($start) || !$this->validateDate($end)) {
            return false;
        }

        return true;
    }

    /**
     * 驗證分頁參數
     *
     * @param integer $firstResult 開始筆數
     * @param integer $maxResults 顯示筆數
     */
    public function validatePagination($firstResult, $maxResults)
    {
        if (!is_null($firstResult) && !$this->isInt($firstResult, true)) {
            throw new \InvalidArgumentException('Invalid first_result', 150610004);
        }

        if (!is_null($maxResults) && !$this->isInt($maxResults, true)) {
            throw new \InvalidArgumentException('Invalid max_results', 150610005);
        }
    }
}
