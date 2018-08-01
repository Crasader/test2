<?php

namespace BB\DurianBundle\User;

use BB\DurianBundle\AbstractIdGenerator;

/**
 * 負責產生出下一個使用者ID
 *
 * @author sliver <sliver@mail.cgs01.com>
 */
class IdGenerator extends AbstractIdGenerator
{
    /**
     * 產生下一個ID
     *
     * @return int
     */
    public function generate()
    {
        $redis = $this->getRedis('sequence');
        $key   = 'user_seq';

        if (!$redis->exists($key)) {
            $msg = 'Cannot generate user sequence id';
            throw new \RuntimeException($msg, 150010087);
        }

        return $redis->incr($key);
    }

    /**
     * 取得目前的ID
     *
     * @return int
     */
    public function getCurrentId()
    {
        $redis = $this->getRedis('sequence');
        $key   = 'user_seq';

        if (!$redis->exists($key)) {
            $msg = 'Cannot get user sequence id';
            throw new \RuntimeException($msg, 150010088);
        }

        return $redis->get($key);
    }
}
