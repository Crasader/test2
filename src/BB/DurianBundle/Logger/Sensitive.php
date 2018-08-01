<?php
namespace BB\DurianBundle\Logger;

use Symfony\Component\DependencyInjection\ContainerAware;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use BB\DurianBundle\Entity\User;
use Monolog\Formatter\LineFormatter;

/**
 * 處理敏感資料
 */
class Sensitive extends ContainerAware
{
    /**
     * urldecode後的Header中HTTP_SENSITIVE_DATA資料
     *
     * @var string
     */
    protected $sensitive = '';

    /**
     * 已解析為陣列的敏感操作資料
     *
     * @var array
     */
    protected $analyzedSensitive = array();

    /**
     * 取得Header中sensitive_data資料並urldecode後回傳
     *
     * @return string
     */
    public function getSensitiveData()
    {
        if (!empty($this->sensitive)) {

            return $this->sensitive;
        }

        $request = $this->container->get('request')->headers;
        $this->sensitive = $request->get('sensitive-data');

        return $this->sensitive;
    }

    /**
     * 回傳解析後的敏感操作資料
     *
     * @return array
     */
    public function getAnalyzedSensitiveData()
    {
        if (!empty($this->analyzedSensitive)) {

            return $this->analyzedSensitive;
        }

        $exploded = explode('&', $this->getSensitiveData());
        foreach ($exploded as $value) {
            $exploded = explode('=', $value);
            if (count($exploded) == 2) {
                $this->analyzedSensitive[$exploded[0]] = urldecode($exploded[1]);
            }
        }

        return $this->analyzedSensitive;
    }

    /**
     * 紀錄操作敏感資料log
     */
    public function writeSensitiveLog()
    {
        $logContent = $this->getSensitiveLog();

        $this->doLog('sensitive.log', $logContent);
    }

    /**
     * 驗證是否有帶操作資訊
     * 如合法則回傳result => true
     * 不合法則回傳result => false ,150240001 errorCode及message
     *
     * @return array
     */
    public function validateHasOperationData()
    {
        $sensitive = $this->getAnalyzedSensitiveData();
        $log = $this->getSensitiveLog();

        // 如果有帶操作資訊則跳出
        if (count($sensitive) != 0) {
            return [ 'result' => true ];
        }

        $log .= ' result: Without operation data';
        $this->doLog('sensitive_not_allowed.log', $log);

        $msg = 'The request not allowed without operation data in header';

        return [
            'result' => false,
            'code'   => 150240001,
            'msg'    => $msg
        ];
    }

    /**
     * 驗證是否為有效的操作者並記錄到log中，
     * 如合法則回傳result => true
     * 不合法則回傳result => false, 24000以上errorCode及message
     *
     * @param mixed $validateTarget 查詢的User 或 domain id
     * @return array
     */
    public function validateAllowedOperator($validateTarget)
    {
        $result = $this->validateAllowedOperatorId();

        if (isset($result['result'])) {
            return $result;
        }

        $result = $this->validateAllowedDomain($validateTarget);

        if (isset($result['result'])) {
            return $result;
        }

        return ['result' => true];
    }

    /**
     * 驗證是否為有效的操作者並記錄到log中，
     * 如合法則回傳result => true
     * 不合法則回傳result => false, 24000以上errorCode及message
     *
     * @return array
     */
    public function validateAllowedOperatorId()
    {
        $sensitive = $this->getAnalyzedSensitiveData();
        $log = $this->getSensitiveLog();

        // 驗證有沒有帶操作資訊，未帶則跳出並回傳資訊
        $error = $this->validateHasOperationData();
        if (!$error['result']) {
            return $error;
        }

        // 如果沒有設定entrance
        if (!isset($sensitive['entrance'])) {
            $log .= ' result: Not define entrance';
            $this->doLog('sensitive_not_allowed.log', $log);

            $msg = 'The request not allowed when operation data not define entrance in header';

            return [
                'result' => false,
                'code'   => 150240002,
                'msg'    => $msg
            ];
        }

        // 如果非管端或會員端則不記錄
        if (!in_array($sensitive['entrance'], array(2, 3))) {
            return ['result' => true];
        }

        // 如果未定義操作者ID欄位
        if (!key_exists('operator_id', $sensitive)) {
            $log .= ' result: Not define operator_id';
            $this->doLog('sensitive_not_allowed.log', $log);

            $msg = 'The request not allowed when operation data not define operator_id in header';

            return [
                'result' => false,
                'code'   => 150240003,
                'msg'    => $msg
            ];
        }

        // 未帶操作者ID
        if ($sensitive['operator_id'] == '') {
            $log .= ' result: operator_id is null';
            $this->doLog('sensitive_not_allowed.log', $log);

            $msg = 'The request not allowed whitout operator_id in header';

            return [
                'result' => false,
                'code'   => 150240004,
                'msg'    => $msg
            ];
        }

        // 如果有設定operator則填入$operator
        $operator = '';
        if (isset($sensitive['operator'])) {
            $operator = trim($sensitive['operator']);
        }

        // 如操作者ID填入0且操作者名稱為nobody則跳出 (與研A C確認之功能)
        if ($sensitive['operator_id'] == 0 && $operator == 'nobody') {
            return ['result' => true];
        }

        $operatorUser = $this->getUser($sensitive['operator_id']);

        // 如果依操作者ID找User物件沒有此User(操作者ID不存在)
        if (!$operatorUser) {
            $log .= " result: operator_id {$sensitive['operator_id']} is invalid";
            $this->doLog('sensitive_not_allowed.log', $log);

            $msg = 'The request not allowed when operator_id is invalid';

            return [
                'result' => false,
                'code'   => 150240005,
                'msg'    => $msg
            ];
        }
    }

    /**
     * 驗證是否為有效的操作者並記錄到log中，
     * 如合法則回傳result => true
     * 不合法則回傳result => false, 24000以上errorCode及message
     *
     * @param mixed $validateTarget 查詢的User 或 domain id
     * @return array
     */
    public function validateAllowedDomain($validateTarget)
    {
        $sensitive = $this->getAnalyzedSensitiveData();
        $log = $this->getSensitiveLog();

        // 驗證帶入的$validateTarget參數如果為User則把domain寫入變數
        $user = null;
        $domain = $validateTarget;
        if ($validateTarget instanceof User) {
            $domain = $validateTarget->getDomain();
            $user = $validateTarget;
        }

        // 如果有設定operator則填入$operator
        $operator = '';
        if (isset($sensitive['operator'])) {
            $operator = trim($sensitive['operator']);
        }

        $operatorUser = $this->getUser($sensitive['operator_id']);

        $log .=  " result: operator_id {$sensitive['operator_id']} $operator (domain {$operatorUser->getDomain()} )";

        // 如果操作者為子帳號則取上層來驗證
        if ($operatorUser->isSub()) {
            $operatorUser = $operatorUser->getParent();
        }

        // 如果操作者與帶入的為不同廳
        if ($operatorUser->getDomain() != $domain) {
            $log .= " not in same domain $domain";
            $this->doLog('sensitive_not_allowed.log', $log);

            $msg = 'The request not allowed when operator is not in same domain';

            return [
                'result' => false,
                'code'   => 150240007,
                'msg'    => $msg
            ];
        }

        if (!$user) { //如$user為空則跳出
            return ['result' => true];
        }

        $operatorUserId = $operatorUser->getId();
        $allParentIds = $user->getAllParentsId();

        // 1.$user不為操作者本人 2.操作者ID不為$user的所有上層ID其中之一
        if ($user->getId() != $operatorUserId && !in_array($operatorUserId, $allParentIds)) {
            $log .= " is not ancestor";
            $this->doLog('sensitive_not_allowed.log', $log);

            $msg = 'The request not allowed when operator is not ancestor';

            return [
                'result' => false,
                'code'   => 150240006,
                'msg'    => $msg
            ];
        }
    }

    /**
     * 驗證特例廳主或及其子帳號的敏感資料
     * 如合法則回傳result => true
     * 不合法則回傳result => false, 24000以上errorCode及message
     *
     * @return array
     */
    public function validateCustomizeDomain()
    {
        $result = $this->validateHasOperationData();

        if (!$result['result']) {
            return $result;
        }

        $result = $this->validateAllowedOperatorId();
        if (isset($result['result'])) {
            return $result;
        }

        $sensitive = $this->getAnalyzedSensitiveData();
        $operatorId = $sensitive['operator_id'];

        if ($this->isCustomizeDomain($operatorId)) {
            return ['result' => true];
        }

        $log = $this->getSensitiveLog();
        $log .= " result: operator_id {$sensitive['operator_id']} is invalid";
        $msg = 'The request not allowed when operator_id is invalid';

        $this->doLog('sensitive_not_allowed.log', $log);

        return [
            'result' => false,
            'code' => 150240005,
            'msg' => $msg
        ];
    }

    /**
     * 驗證operatorId為特例廳主或及其子帳號
     * @param integer $operatorId
     * @return boolean
     */
    private function isCustomizeDomain($operatorId)
    {
        //TT娛樂城,盈丰,新葡京娛樂
        $customizeDomain = [75, 84, 164];
        $operator = $this->getUser($operatorId);

        // User屬於特例群組
        if (in_array($operator->getId(), $customizeDomain)) {
            return true;
        }

        if ($operator->isSub()) {
            $parent = $operator->getParent();

            // Parent屬於特例群組
            if (in_array($parent->getId(), $customizeDomain)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 回傳使用者User物件
     *
     * @param integer $operatorId
     * @return User
     */
    private function getUser($operatorId)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        return $em->find('BBDurianBundle:User', $operatorId);
    }

    /**
     * 回傳敏感資料LOG
     * @return string
     */
    private function getSensitiveLog()
    {
        $request = $this->container->get('request');

        $method = $request->getMethod();
        $serverIp = $request->getClientIp();
        $uri = $request->getUri();
        $sensitive = $this->getSensitiveData();

        return "$serverIp $method $uri sensitive-data:$sensitive";
    }

    /**
     * 設定並寫入LOG
     *
     * @param string $filename
     * @param string $logContent
     */
    private function doLog($filename, $logContent)
    {
        $env = $this->container->get('kernel')->getEnvironment();
        $envDir = $this->container->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . $env;
        $stream = $envDir . DIRECTORY_SEPARATOR . $filename;

        $handler = new StreamHandler($stream, Logger::INFO);
        $remoteIp = $this->container->get('request')->server->get('REMOTE_ADDR');

        // 設定log格式，加上remote ip
        $format = $remoteIp . ' - ' . LineFormatter::SIMPLE_FORMAT;
        $handler->setFormatter(new LineFormatter($format));

        $logger = $this->container->get('durian.logger_manager')->setUpLogger($filename, $handler);
        $logger->addInfo($logContent);
        $logger->popHandler()->close();
    }
}
