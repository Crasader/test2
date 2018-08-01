<?php

namespace BB\DurianBundle\Tests\Deposit\Entry;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class IdGeneratorTest extends WebTestCase
{
    /**
     * deposit entry id generator
     *
     * @var IdGenerator
     */
    private $generator;

    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures(array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositSequenceData',
        ));

        $this->generator = $this->getContainer()->get('durian.deposit_entry_id_generator');
    }

    /**
     * 測試回傳入款明細ID三次
     */
    public function testGenerateDepositEntryIdThreeTimes()
    {
        $idGenerator = $this->generator;
        $now = new \DateTime();
        $date = $now->format('Ymd');

        $this->assertEquals(sprintf('%s%s', $date, '0000000001'), $idGenerator->generate());
        $this->assertEquals(sprintf('%s%s', $date, '0000000002'), $idGenerator->generate());
        $this->assertEquals(sprintf('%s%s', $date, '0000000003'), $idGenerator->generate());
    }
}
