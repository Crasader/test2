<?php

namespace BB\DurianBundle\Captcha;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\User;

/**
 * Captcha Genie
 */
class Genie extends ContainerAware
{
    /**
     * 格式：數字
     */
    const FORMAT_DIGIT = 1;

    /**
     * 格式：文字
     */
    const FORMAT_LETTER = 2;

    /**
     * 格式：數字 + 文字
     */
    const FORMAT_ALPHANUMERIC = 3;

    /**
     * 合法的格式
     *
     * @var array
     */
    public static $legalFormat = [
        self::FORMAT_DIGIT,
        self::FORMAT_LETTER,
        self::FORMAT_ALPHANUMERIC
    ];

    /**
     * 數字範圍
     *
     * @var string
     */
    private $digit = '1234567890';

    /**
     * 文字範圍
     *
     * @var string
     */
    private $letter = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

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
     * 建立使用者的驗證碼
     *
     * @param User $user 使用者
     * @param integer $identifier 識別符
     * @param integer $setting 驗證碼設定值，支援設定如下
     *        integer $setting['format'] 格式
     *        integer $setting['length'] 長度
     *        integer $setting['ttl'] 在redis的存放時間
     * @return string
     * @throws \RuntimeException
     */
    public function create(User $user, $identifier, $setting)
    {
        $format = $setting['format'];
        $length = $setting['length'];
        $ttl = $setting['ttl'];

        $userId = $user->getId();
        if ($this->get($userId, $identifier)) {
            throw new \RuntimeException('Captcha already exists', 150400001);
        }

        $captcha = '';
        $characters = $this->digit;

        if ($format == self::FORMAT_LETTER) {
            $characters = $this->letter;
        }

        if ($format == self::FORMAT_ALPHANUMERIC) {
            $characters .= $this->letter;
        }

        // generate captcha
        while(strlen($captcha) < $length) {
            $charLength = strlen($characters);
            $mod = rand() % $charLength;

            $captcha .= substr($characters, $mod, 1);
        }

        // store in redis
        $redis = $this->getRedis();
        $key = $userId . '_' . $identifier;
        $captchaKey = "captcha_user_$key";

        $redis->set($captchaKey, $captcha);
        $redis->expire($captchaKey, $ttl);

        return $captcha;
    }

    /**
     * 取得驗證碼
     *
     * @param integer $userId 使用者ID
     * @param integer $identifier 驗證碼識別符
     * @return string
     */
    public function get($userId, $identifier)
    {
        $redis = $this->getRedis();

        $key = $userId . '_' . $identifier;
        $captchaKey = "captcha_user_$key";

        return $redis->get($captchaKey);
    }

    /**
     * 刪除驗證碼
     *
     * @param integer $userId 使用者ID
     * @param integer $identifier 驗證碼識別符
     */
    public function remove($userId, $identifier)
    {
        $redis = $this->getRedis();
        $key = $userId . '_' . $identifier;
        $captchaKey = "captcha_user_$key";

        $redis->del($captchaKey);
    }
}
