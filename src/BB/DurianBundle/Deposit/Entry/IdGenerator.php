<?php

namespace BB\DurianBundle\Deposit\Entry;

use BB\DurianBundle\AbstractIdGenerator;

/**
 * 負責產生出下一個入款單號
 */
class IdGenerator extends AbstractIdGenerator
{
    /**
     * 產生下一個ID, id 格式為 Ymd + 十碼數字, 如果未滿十碼則自動補0
     *
     * @return int
     */
    public function generate()
    {
        $query = 'INSERT INTO deposit_sequence (id) VALUES (null)';

        $connection = $this->getEntityManager()->getConnection();
        $connection->executeQuery($query);

        $now = new \DateTime();
        $generateId = str_pad($connection->lastInsertId(), 10, '0', STR_PAD_LEFT);

        return sprintf('%s%s', $now->format('Ymd'), $generateId);
    }
}
