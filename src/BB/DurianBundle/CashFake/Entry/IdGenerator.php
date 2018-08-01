<?php

namespace BB\DurianBundle\CashFake\Entry;

use BB\DurianBundle\AbstractIdGenerator;

/**
 * 負責產生出快開額度交易明細下一個ID
 *
 * @author sliver <sliver@mail.cgs01.com>
 */
class IdGenerator extends AbstractIdGenerator
{
    /**
     * @var int 每次ID增加量
     */
    private $increment = 1;

    /**
     * 產生下一個ID
     *
     * @return int
     */
    public function generate()
    {
        $redis = $this->getRedis('sequence');
        $key   = 'cashfake_seq';

        if (!$redis->exists($key)) {
            $msg = 'Cannot generate cashfake sequence id';
            throw new \RuntimeException($msg, 150050009);
        }

        return $redis->incrby($key, $this->increment);
    }

    /**
     * 設定每次ID增加量
     *
     * @param int $val
     * @return IdGenerator
     */
    public function setIncrement($val)
    {
        $this->increment = $val;

        return $this;
    }
}