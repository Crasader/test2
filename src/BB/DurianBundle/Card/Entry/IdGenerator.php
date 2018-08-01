<?php

namespace BB\DurianBundle\Card\Entry;

use BB\DurianBundle\AbstractIdGenerator;

/**
 * 負責產生出租卡交易明細下一個ID
 *
 * @author sliver <sliver@mail.cgs01.com>
 */
class IdGenerator extends AbstractIdGenerator
{
    /**
     * 每次ID增加量
     *
     * @var int
     */
    private $increment = 1;

    /**
     * 產生下一個ID
     *
     * @return int
     * @throws \RuntimeException
     */
    public function generate()
    {
        $redis = $this->getRedis('sequence');
        $key   = 'card_seq';

        if (!$redis->exists($key)) {
            $msg = 'Cannot generate card sequence id';
            throw new \RuntimeException($msg, 150030007);
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
