<?php

namespace BB\DurianBundle\Withdraw\Entry;

use BB\DurianBundle\AbstractIdGenerator;

/**
 * 負責產生出現金出款明細下一個ID
 *
 * @author dean <dean8006@gmail.com>
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
        $key   = 'cash_withdraw_seq';

        if (!$redis->exists($key)) {
            $msg = 'Cannot generate cash withdraw sequence id';
            throw new \RuntimeException($msg, 380012);
        }

        return $redis->incrby($key, $this->increment);
    }
}
