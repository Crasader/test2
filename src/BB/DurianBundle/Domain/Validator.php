<?php

namespace BB\DurianBundle\Domain;

use BB\DurianBundle\Entity\DomainConfig;
use Symfony\Component\DependencyInjection\ContainerAware;

class Validator extends ContainerAware
{
    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name EntityManager名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 驗證廳名可用
     *
     * @param string $name 廳名
     */
    public function validateName($name)
    {
        $len = mb_strlen($name, 'UTF-8');
        $max = DomainConfig::MAX_NAME_LENGTH;
        $min = DomainConfig::MIN_NAME_LENGTH;

        if ($len > $max || $len < $min) {
            throw new \InvalidArgumentException('Invalid name length given', 150360015);
        }

        if (preg_match('/[><\'"=]/', $name)) {
            throw new \InvalidArgumentException('Invalid name character given', 150360016);
        }

        $repo = $this->getEntityManager('share')->getRepository('BBDurianBundle:DomainConfig');

        $dcName = $repo->findOneBy(['name' => $name, 'removed' => 0]);

        if ($dcName) {
            throw new \RuntimeException('Name already exist', 150360017);
        }
    }

    /**
     * 驗證登入代碼
     *
     * @param string $loginCode 登入代碼
     */
    public function validateLoginCode($loginCode)
    {
        // 不可小於下限
        if (strlen($loginCode) < DomainConfig::LOGIN_CODE_LENGTH_MIN) {
            throw new \InvalidArgumentException('Invalid login code', 150360020);
        }

        // 不可大於上限
        if (strlen($loginCode) > DomainConfig::LOGIN_CODE_LENGTH_MAX) {
            throw new \InvalidArgumentException('Invalid login code', 150360020);
        }

        // 規則為小寫+數字
        if (!preg_match('/^[a-z0-9]+$/', $loginCode)) {
            throw new \InvalidArgumentException('Invalid login code character given', 150360021);
        }
    }
}
