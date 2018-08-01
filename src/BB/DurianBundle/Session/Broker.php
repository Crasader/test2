<?php

namespace BB\DurianBundle\Session;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\User;

/**
 * Session 操作物件
 */
class Broker extends ContainerAware
{
    /**
     * Session 存放時間設為7天
     * @var integer
     */
    private $ttl = 604800;

    /**
     * 依據 Session Id, 檢查使用者是否有 Session
     *
     * @param string $sessionId
     * @return boolean
     */
    public function existsBySessionId($sessionId)
    {
        $redis = $this->getRedis();

        $sessionKey = 'session_' . $sessionId;

        if ($this->checkIsInRemoveList($sessionId)) {
            return false;
        }

        if ($sessionKey && $redis->exists($sessionKey)) {
            return true;
        }

        return false;
    }

    /**
     * 依據使用者編號, 檢查使用者是否有 Session
     *
     * @param integer $userId
     * @return boolean
     */
    public function existsByUserId($userId)
    {
        $redis = $this->getRedis();

        $mapKey = "session_user_{$userId}_map";
        $sessionId = $redis->lindex($mapKey, 0);

        if (!$sessionId) {
            return false;
        }

        return $this->existsBySessionId($sessionId);
    }

    /**
     * 建立使用者的 Session
     *
     * @param User $user 使用者
     * @param boolean $force 強制建立新session
     * @param array $loginInfo 登入訊息
     * @return string
     */
    public function create(User $user, $force = false, $loginInfo = [])
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $currencyOperator = $this->container->get('durian.currency');

        $userId = $user->getId();

        if (!$force && $this->existsByUserId($userId)) {
            throw new \RuntimeException('Session already exists', 150330002);
        }

        $repo = $em->getRepository('BBDurianBundle:UserAncestor');
        $ancestors = $repo->findBy(['user' => $user], ['depth' => 'ASC']);

        $ancestorData = [];

        foreach ($ancestors as $ancestor) {
            $ancestorData[] = $ancestor->getAncestor()->getId();
        }

        $userData = $user->toArray();
        unset(
            $userData['password_expire_at'],
            $userData['password_reset'],
            $userData['err_num'],
            $userData['bankrupt'],
            $userData['enable'],
            $userData['block'],
            $userData['last_bank']
        );

        // 產生 SessionId
        $sessionId = $this->generateSessionId();

        $mapKey = "session_user_{$userId}_map";
        $sessionKey = 'session_' . $sessionId;

        $sessionData = [
            'session:id' => $sessionId,
            'session:created_at' => (new \DateTime)->format(\DateTime::ISO8601),
            'session:modified_at' => null
        ];

        foreach ($userData as $field => $value) {
            $sessionData["user:$field"] = $value;
        }

        $sessionData['user:all_parents']   = implode(',', $ancestorData);
        $sessionData['user:ingress']       = null;
        $sessionData['user:client_os']     = null;
        $sessionData['user:last_login_ip'] = null;

        if ($loginInfo) {
            $sessionData['user:ingress']       = $loginInfo['ingress'];
            $sessionData['user:client_os']     = $loginInfo['client_os'];
            $sessionData['user:last_login_ip'] = $loginInfo['last_login_ip'];
        }

        // 使用者交易機制放到session
        if ($user->getCash()) {
            $currency = $user->getCash()->getCurrency();
            $sessionData['cash:currency'] = $currencyOperator->getMappedCode($currency);
        }

        if ($user->getCashFake()) {
            $currency = $user->getCashFake()->getCurrency();
            $sessionData['cash_fake:currency'] = $currencyOperator->getMappedCode($currency);
        }

        if (count($user->getCredits())) {
            $sessionData['credit'] = true;
        }

        if ($user->getCard()) {
            $sessionData['card'] = true;
        }

        $repo = $em->getRepository('BBDurianBundle:UserPayway');
        $payway = $repo->getUserPayway($user);

        if ($payway && $payway->isOutsideEnabled()) {
            $sessionData['outside'] = true;
        }

        $redis = $this->getRedis();
        $redis->lpush($mapKey, $sessionId);
        $redis->hmset($sessionKey, $sessionData);

        $redis->expire($sessionKey, $this->ttl);
        $redis->expire($mapKey, $this->ttl);

        return $sessionId;
    }

    /**
     * 用session id建立使用者的 Session
     *
     * @param User $user 使用者
     * @param string $sessionId session_id
     * @return string
     */
    public function createBySessionId(User $user, $sessionId)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $currencyOperator = $this->container->get('durian.currency');

        $userId = $user->getId();

        $repo = $em->getRepository('BBDurianBundle:UserAncestor');
        $ancestors = $repo->findBy(['user' => $user], ['depth' => 'ASC']);

        $ancestorData = [];

        foreach ($ancestors as $ancestor) {
            $ancestorData[] = $ancestor->getAncestor()->getId();
        }

        $userData = $user->toArray();
        unset(
            $userData['password_expire_at'],
            $userData['password_reset'],
            $userData['err_num'],
            $userData['bankrupt'],
            $userData['enable'],
            $userData['block'],
            $userData['last_bank']
        );

        $mapKey = "session_user_{$userId}_map";
        $sessionKey = 'session_' . $sessionId;

        $sessionData = [
            'session:id' => $sessionId,
            'session:created_at' => (new \DateTime)->format(\DateTime::ISO8601),
            'session:modified_at' => null
        ];

        foreach ($userData as $field => $value) {
            $sessionData["user:$field"] = $value;
        }

        $sessionData['user:all_parents'] = implode(',', $ancestorData);
        $sessionData['user:ingress'] = null;
        $sessionData['user:client_os'] = null;
        $sessionData['user:last_login_ip'] = null;

        // 將外接額度payway設為true
        $sessionData['outside'] = true;

        $redis = $this->getRedis();
        $redis->lpush($mapKey, $sessionId);
        $redis->hmset($sessionKey, $sessionData);

        $redis->expire($sessionKey, $this->ttl);
        $redis->expire($mapKey, $this->ttl);

        return $sessionId;
    }

    /**
     * 依據 session id 取得 Session 資料
     *
     * @param string $sessionId
     * @return array
     */
    public function getBySessionId($sessionId)
    {
        $redis = $this->getRedis();

        $sessionKey = 'session_' . $sessionId;
        $sessionData = $redis->hgetall($sessionKey);

        if (!$sessionData || $this->checkIsInRemoveList($sessionId)) {
            throw new \RuntimeException('Session not found', 150330001);
        }

        $out = [];

        foreach ($sessionData as $key => $value) {
            if (strpos($key, ':')) {
                list($category, $field) = explode(':', $key);

                if (!isset($out[$category])) {
                    $out[$category] = [];
                }

                if ($value === '') {
                    $value = null;
                }

                if ($category === 'rd_info') {
                    $value = json_decode($value, true);
                }

                $out[$category][$field] = $value;
            } else {
                $out[$key] = (bool) $value;
            }
        }

        $out['user']['all_parents'] = [];

        if ($sessionData['user:all_parents']) {
            $out['user']['all_parents'] = explode(',', $sessionData['user:all_parents']);
        }

        //要轉換為 Boolean 的欄位名稱
        $mapToBoolean = ['sub', 'test', 'hidden_test'];

        foreach ($mapToBoolean as $value) {
            $out['user'][$value] = false;

            if ($sessionData['user:' . $value]) {
                $out['user'][$value] = true;
            }
        }

        return $out;
    }

    /**
     * 依據 user id 取得 Session 資料
     *
     * @parame string $userId
     * @return array
     */
    public function getByUserId($userId)
    {
        $redis = $this->getRedis();

        $mapKey = "session_user_{$userId}_map";

        // 取最新的sessionId
        $sessionId = $redis->lindex($mapKey, 0);

        if (!$sessionId) {
            throw new \RuntimeException('Session not found', 150330001);
        }

        return $this->getBySessionId($sessionId);
    }

    /**
     * 刪除 Session 資料
     *
     * @param string $sessionId
     */
    public function remove($sessionId)
    {
        $redis = $this->getRedis();

        $sessionKey = 'session_' . $sessionId;
        $redis->del($sessionKey);
    }

    /**
     * 依據 user id 刪除 Session 資料
     *
     * @param integer $userId 使用者id
     */
    public function removeByUserId($userId)
    {
        $redis = $this->getRedis();

        $mapKey = "session_user_{$userId}_map";

        while ($sessionId = $redis->rpop($mapKey)) {
            $sessionKey = 'session_' . $sessionId;
            $redis->del($sessionKey);
        }

        $redis->del($mapKey);
     }

    /**
     * 將欲刪除的parent、depth與role放到刪除清單
     *
     * @param integer $parentId 上層id
     * @param integer $depth 刪除層數
     * @param integer $role 刪除的特定層級
     */
    public function pushToRemoveList($parentId, $depth, $role)
    {
        $redis = $this->getRedis();

        $createdAt = (new \DateTime)->format('Y-m-d H:i:s');

        $redis->lpush('session_remove_queue', "$parentId,$depth,$role,$createdAt");
    }

    /**
     * 設定 session 的維護資訊
     *
     * @param int $code 遊戲代碼
     * @param \Datetime $beginAt 維護開始時間
     * @param \Datetime $endAt 維護結束時間
     * @param string $msg 維護訊息
     */
    public function setMaintainInfo($code, $beginAt, $endAt, $msg)
    {
        $redis = $this->getRedis();

        $beginAt = $beginAt->format('Y-m-d H:i:s');
        $endAt = $endAt->format('Y-m-d H:i:s');
        $data = [
            'begin_at' => $beginAt,
            'end_at' => $endAt,
            'msg' => $msg
        ];

        $redis->hmset('session_maintain', $code, json_encode($data));
    }

    /**
     * 取得 session 的維護資訊
     *
     * @return array
     */
    public function getMaintainInfo()
    {
        $redis = $this->getRedis();

        $maintainInfo = $redis->hgetall('session_maintain');
        $maintain = [];
        $now = new \Datetime('now');

        foreach ($maintainInfo as $key => $value) {
            $data = json_decode($value, true);

            $maintainBeginAt = new \Datetime($data['begin_at']);
            $maintainEndAt = new \Datetime($data['end_at']);

            if ($now >= $maintainBeginAt && $now <= $maintainEndAt) {
                $maintain[$key] = [
                    'begin_at' => $maintainBeginAt->format(\DateTime::ISO8601),
                    'end_at' => $maintainEndAt->format(\DateTime::ISO8601),
                    'msg' => $data['msg']
                ];
            }
        }

        return $maintain;
    }

    /**
     * 新增 session 的白名單ip
     *
     * @param string $ip 白名單ip
     */
    public function addWhitelistIp($ip)
    {
        $redis = $this->getRedis();

        $redis->sadd('session_whitelist', $ip);
    }

    /**
     * 刪除 session 的白名單ip
     *
     * @param string $ip 白名單ip
     */
    public function removeWhitelistIp($ip)
    {
        $redis = $this->getRedis();

        $redis->srem('session_whitelist', $ip);
    }

    /**
     * 取得白名單列表
     *
     * @return array
     */
    public function getWhitelist()
    {
        $redis = $this->getRedis();

        return $redis->smembers('session_whitelist');
    }

    /**
     * 取得線上人數列表
     *
     * @param integer $domain 廳
     * @param integer $inTime 在線幾分鐘
     * @param integer $maxResults 筆數
     * @return array
     */
    public function getOnlineList($domain, $inTime, $maxResults)
    {
        $redis = $this->getRedis();

        $now = new \Datetime('now');
        $cloneNow = clone $now;
        $at = $cloneNow->sub(new \DateInterval("PT{$inTime}M"));
        $startTime = $at->format('YmdHis');
        $endTime = $now->format('YmdHis');

        return $redis->zrevrangebyscore("onlineList:domain:$domain", $endTime, $startTime, ['Limit' => [0, $maxResults]]);
    }

    /**
     * 依據使用者帳號取得線上人數列表
     *
     * @param integer $domain 廳
     * @param stirng $username 使用者帳號
     * @return array
     */
    public function getOnlineListByUsername($domain, $username)
    {
        $redis = $this->getRedis();
        $from = 0;
        $list = [];

        do {
            $result = $redis->zscan("onlineList:domain:$domain", $from, ['MATCH' => "*:$username", 'COUNT' => 1000]);
            $from = $result[0];
            $list[] = $result[1];
        } while ($result[0] != 0);

        return $list;
    }

    /**
     * 取得線上人數
     *
     * @param integer $domain 廳
     * @param integer $inTime 在線幾分鐘
     * @return array
     */
    public function getTotalOnline($domain, $inTime)
    {
        $redis = $this->getRedis();

        $now = new \Datetime('now');
        $cloneNow = clone $now;
        $at = $cloneNow->sub(new \DateInterval("PT{$inTime}M"));
        $startTime = $at->format('YmdHis');
        $endTime = $now->format('YmdHis');

        return $redis->zcount("onlineList:domain:$domain", $startTime, $endTime);
    }

    /**
     * 建立一次性Session
     *
     * @param string $sessionId
     * @return string
     */
    public function createOneTimeSession($sessionId)
    {
        $redis = $this->getRedis();
        $key = '';
        $id = '';

        while (true) {
            $randomString = openssl_random_pseudo_bytes(20);
            $id = sha1($randomString);
            $key = 'ots_' . $id;

            if (!$redis->exists($key)) {
                break;
            }
        }

        // 只允許 1 分鐘內有效
        $redis->set($key, $sessionId, 'EX', 60, 'NX');

        return $id;
    }

    /**
     * 回傳 One-Time Session 指定的 Session Id
     *
     * @param string $id OTS id
     * @return string
     */
    public function getOneTimeSession($id)
    {
        $redis = $this->getRedis();
        $key = 'ots_' . $id;

        $sid = $redis->get($key);
        $redis->del($key);

        return $sid;
    }

    /**
     * 依據sessionId新增Session的研發資訊
     *
     * @param string $sessionId 使用者sessionId
     * @param array $rdInfo 研發資訊
     * @return array
     */
    public function setSessionRdInfo($sessionId, $rdInfo)
    {
        $this->getBySessionId($sessionId);
        $redis = $this->getRedis();
        $rdInfo = $this->handleRdInfo($rdInfo);

        if (isset($rdInfo['lobby_switch'])) {
            $lobbySwitch = $rdInfo['lobby_switch'];

            // 長度 1000 可塞 83 個開關
            if (strlen(json_encode($lobbySwitch)) > 1000) {
                throw new \InvalidArgumentException('Invalid lobby_switch length given', 150330019);
            }
        }

        if (isset($rdInfo['mem_domain'])) {
            $memDomain = str_replace(" ", "", $rdInfo['mem_domain']);

            if (strlen($memDomain) > 200) {
                throw new \InvalidArgumentException('Invalid mem_domain length given', 150330024);
            }
        }

        $sessionKey = 'session_' . $sessionId;
        $rdData = [];

        if (is_array($rdInfo)) {
            foreach ($rdInfo as $key => $value) {
                $rdData["rd_info:$key"] = json_encode($value);
            }
        }

        $redis->hmset($sessionKey, $rdData);

        return $this->getBySessionId($sessionId);
    }

    /**
     * 回傳 Redis Client
     *
     * @return \Predis\Client
     */
    private function getRedis()
    {
        return $this->container->get('snc_redis.cluster');
    }

    /**
     * 檢查session是否在刪除列表中
     *
     * @param string $sessionId
     * @return boolean
     */
    private function checkIsInRemoveList($sessionId)
    {
        $redis = $this->getRedis();
        $sessionKey = 'session_' . $sessionId;

        $removeList = $redis->lrange('session_remove_queue', 0, -1);
        $fields = $redis->hmget($sessionKey, 'session:created_at', 'user:all_parents', 'user:id', 'user:role');
        $createdAt = $fields[0];
        $allParents = $fields[1];
        $userId = $fields[2];
        $role = $fields[3];

        $allParents = explode(',', $allParents);
        $createdAt = new \DateTime($createdAt);

        foreach ($removeList as $row) {
            $value = explode(',', $row);
            $delParentId = $value[0];
            $delDepth = $value[1];
            $delRole = $value[2];
            $deletedAt = new \DateTime($value[3]);

            if ($createdAt > $deletedAt) {
                break;
            }

            // 處理不分廳的狀況
            if (!$delParentId) {
                if (!$delRole && !$delDepth) {
                    return true;
                }

                if ($delRole && $role == $delRole) {
                    return true;
                }

                if ($delDepth && count($allParents) <= $delDepth) {
                    return true;
                }

                continue;
            }

            // 處理特定role
            if ($delRole) {
                if (in_array($delParentId, $allParents) && $role == $delRole) {
                    return true;
                }

                continue;
            }

            // parent自己也算被刪除的使用者
            if ($userId == $delParentId) {
                return true;
            }

            // 往上找depth層的parent
            $delParents = array_slice($allParents, 0, $delDepth);

            // 沒有帶depth找全部上層
            if (!$delDepth) {
                $delParents = $allParents;
            }

            if (in_array($delParentId, $delParents)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 產生sessionId
     *
     * @return string
     */
    private function generateSessionId()
    {
        // 產生完需檢查是否跟現有的sessionId重複，若有重複必須重新產生
        while (true) {
            $randomString = openssl_random_pseudo_bytes(20);
            $sessionId = sha1($randomString);

            if (!$this->existsBySessionId($sessionId)) {
                return $sessionId;
            }
        }
    }

    /**
     * 處理研發資訊格式
     *
     * @param array $rdInfo
     * @return array
     */
    private function handleRdInfo($rdInfo)
    {
        $ret = [];

        // 處理lobbySwitch
        if (isset($rdInfo['lobby_switch'])) {
            $lobbySwitch = $rdInfo['lobby_switch'];
            $key = $lobbySwitch['key'];
            $value = $lobbySwitch['value'];

            if (count($key) !== count($value)) {
                throw new \InvalidArgumentException('Invalid lobby_switch key value length given', 150330020);
            }

            foreach ($key as $index => $keyName) {
                if (!$keyName) {
                    throw new \InvalidArgumentException('No lobby_switch key specified', 150330027);
                }

                if (!is_numeric($keyName)) {
                    throw new \InvalidArgumentException('Invalid lobby_switch key given', 150330022);
                }

                if ($value[$index] === '') {
                    throw new \InvalidArgumentException('No lobby_switch value specified', 150330023);
                }

                $ret['lobby_switch'][$keyName] = (boolean) $value[$index];
            }
        }

        // 處理memDomain
        if (isset($rdInfo['mem_domain'])) {
            $memDomain = trim($rdInfo['mem_domain']);

            if ($memDomain === '') {
                throw new \InvalidArgumentException('No mem_domain specified', 150330025);
            }

            // 客端主Domain資訊須為網址格式
            $pattern = "/^(?:https?:\/\/(?:www\.)?|www\.)[a-z0-9]+(?:[-.][a-z0-9]+)*\.[a-z]{2,5}(?::[0-9]{1,5})?(?:\/\S*)?$/";

            if (!preg_match($pattern, $memDomain)) {
                throw new \InvalidArgumentException('Invalid mem_domain given', 150330026);
            }

            if ($memDomain) {
                $ret['mem_domain'] = $memDomain;
            }
        }

        return $ret;
    }
}
