<?php

namespace BB\DurianBundle\Bitcoin\Deposit\Entry;

use BB\DurianBundle\AbstractIdGenerator;

/**
 * 負責產生出比特幣入款交易明細下一個ID
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
        $key = 'bitcoin_deposit_seq';

        if (!$redis->exists($key)) {
            $msg = 'Cannot generate bitcoin deposit sequence id';
            throw new \RuntimeException($msg, 150930001);
        }

        $now = new \DateTime();
        $generateId = $redis->incrby($key, $this->increment);

        return sprintf('%d%010d', $now->format('Ymd'), $generateId);
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
