<?php

namespace BB\DurianBundle;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * 負責產生出下一個ID
 *
 * @author sliver <sliver@mail.cgs01.com>
 */
abstract class AbstractIdGenerator extends ContainerAware
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var \Predis\Client
     */
    private $redis;

    /**
     * 產生新的Id
     */
    abstract public function generate();

    /**
     * 回傳redis client
     *
     * @param string $name Redis名稱
     * @return \Predis\Client
     */
    protected function getRedis($name = 'default')
    {
        if (!$this->redis) {
            $this->redis = $this->container->get("snc_redis.{$name}");
        }

        return $this->redis;
    }

    /**
     * 回傳EntityManager
     *
     * @param string $name EntityManager 名稱
     * @return EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->container->get('doctrine')->getManager($name);
    }
}
