<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 使用者序列產生id
 *
 * @ORM\Entity
 * @ORM\Table(name = "deposit_sequence")
 */
class DepositSequence
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;
}
