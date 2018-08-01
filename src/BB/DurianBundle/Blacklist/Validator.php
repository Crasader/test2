<?php

namespace BB\DurianBundle\Blacklist;

use Symfony\Component\DependencyInjection\ContainerAware;

class Validator extends ContainerAware
{
    /**
     * 驗證是否在黑名單中
     *
     * $criteria包含以下參數
     *   string  $account      銀行帳號
     *   string  $identityCard 身分證字號
     *   string  $nameReal     真實姓名
     *   string  $telephone    電話
     *   string  $email        信箱
     *   string  $ip           IP
     *   boolean $systemLock   是否系統封鎖
     *
     * @param array   $criteria 查詢條件
     * @param integer $domain   廳主
     */
    public function validate($criteria, $domain = null)
    {
        if (empty($criteria)) {
            return;
        }

        $validator = $this->container->get('durian.validator');

        if (isset($criteria['account']) && $criteria['account']) {
            $this->validateAccount($criteria['account'], $domain);
        }

        if (isset($criteria['identity_card']) && $criteria['identity_card']) {
            $this->validateIdentityCard($criteria['identity_card'], $domain);
        }

        if (isset($criteria['name_real']) && $criteria['name_real']) {
            $validator->validateEncode($criteria['name_real']);
            $this->validateNameReal($criteria['name_real'], $domain);
        }

        if (isset($criteria['telephone']) && $criteria['telephone']) {
            $this->validateTelephone($criteria['telephone'], $domain);
        }

        if (isset($criteria['email']) && $criteria['email']) {
            $this->validateEmail($criteria['email'], $domain);
        }

        if (isset($criteria['ip']) && $criteria['ip']) {
            $system = true;

            if (isset($criteria['system_lock'])) {
                $system = $criteria['system_lock'];
            }

            $this->validateIp($criteria['ip'], $system, $domain);
        }
    }

    /**
     * 驗證銀行帳號
     *
     * @param string  $account 銀行帳號
     * @param integer $domain  廳主
     */
    private function validateAccount($account, $domain = null)
    {
        $em = $this->getEntityManager('share');

        $criteria['account'] = trim($account);
        $blacklist = $em->getRepository('BBDurianBundle:Blacklist')->getBlacklistSingleBy($criteria, $domain);

        if ($blacklist) {
            throw new \RuntimeException('This account has been blocked', 150650015);
        }
    }

    /**
     * 驗證身分證字號
     *
     * @param string  $identityCard 身分證字號
     * @param integer $domain       廳主
     */
    private function validateIdentityCard($identityCard, $domain = null)
    {
        $em = $this->getEntityManager('share');

        $criteria['identity_card'] = trim($identityCard);
        $blacklist = $em->getRepository('BBDurianBundle:Blacklist')->getBlacklistSingleBy($criteria, $domain);

        if ($blacklist) {
            throw new \RuntimeException('This identity_card has been blocked', 150650016);
        }
    }

    /**
     * 驗證真實姓名
     *
     * @param string  $nameReal 真實姓名
     * @param integer $domain   廳主
     */
    private function validateNameReal($nameReal, $domain = null)
    {
        $em = $this->getEntityManager('share');
        $parameterHandler = $this->container->get('durian.parameter_handler');

        $nameReal = trim($nameReal);
        $regex = '/-(\*|[1-9][0-9]?)$/';

        // 過濾特殊字元
        $nameReal = $parameterHandler->filterSpecialChar($nameReal);

        if (preg_match($regex, $nameReal, $matches)) {
            $subStrOriNameleng = strlen($nameReal) - strlen($matches[0]);
            $nameReal = substr($nameReal, 0, $subStrOriNameleng);
        }

        $criteria['name_real'] = $nameReal;
        $blacklist = $em->getRepository('BBDurianBundle:Blacklist')->getBlacklistSingleBy($criteria, $domain);

        if ($blacklist) {
            throw new \RuntimeException('This name_real has been blocked', 150650017);
        }
    }

    /**
     * 驗證電話
     *
     * @param string  $telephone 電話
     * @param integer $domain    廳主
     */
    private function validateTelephone($telephone, $domain = null)
    {
        $em = $this->getEntityManager('share');

        $criteria['telephone'] = trim($telephone);
        $blacklist = $em->getRepository('BBDurianBundle:Blacklist')->getBlacklistSingleBy($criteria, $domain);

        if ($blacklist) {
            throw new \RuntimeException('This telephone has been blocked', 150650018);
        }
    }

    /**
     * 驗證信箱
     *
     * @param string  $email  信箱
     * @param integer $domain 廳主
     */
    private function validateEmail($email, $domain = null)
    {
        $em = $this->getEntityManager('share');

        $criteria['email'] = trim($email);
        $blacklist = $em->getRepository('BBDurianBundle:Blacklist')->getBlacklistSingleBy($criteria, $domain);

        if ($blacklist) {
            throw new \RuntimeException('This email has been blocked', 150650019);
        }
    }

    /**
     * 驗證IP
     *
     * @param string  $ip     IP
     * @param boolean $system 是否系統封鎖
     * @param integer $domain 廳主
     */
    private function validateIp($ip, $system, $domain = null)
    {
        $em = $this->getEntityManager('share');

        $criteria['ip'] = trim($ip);

        if (!$system) {
            $criteria['system_lock'] = false;
        }

        $blacklist = $em->getRepository('BBDurianBundle:Blacklist')->getBlacklistSingleBy($criteria, $domain);

        if ($blacklist) {
            throw new \RuntimeException('This ip has been blocked', 150650020);
        }
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
    }
}
