<?php

namespace BB\DurianBundle\Domain;

use BB\DurianBundle\AbstractIdGenerator;

/**
 * 負責產生出下一個廳主ID
 *
 * @author sweet <pigsweet7834@gmail.com>
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
        $queryUser = 'SELECT MAX(id)+1 FROM `user` WHERE id < 20000000';
        $queryRemovedUser = 'SELECT MAX(user_id)+1 FROM `removed_user` WHERE user_id < 20000000';

        $conn = $this->getEntityManager()->getConnection();
        $connShare = $this->getEntityManager('share')->getConnection();
        $maxUserId = $conn->executeQuery($queryUser)->fetchColumn(0);
        $maxRemovedUserId = $connShare->executeQuery($queryRemovedUser)->fetchColumn(0);

        return max($maxUserId, $maxRemovedUserId);
    }
}
