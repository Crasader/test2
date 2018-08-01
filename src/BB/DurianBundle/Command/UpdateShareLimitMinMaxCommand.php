<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateShareLimitMinMaxCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * 一次抓幾筆佔成
     *
     * @var int
     */
    private $limit = 1000;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:update-sharelimit-min-max')
            ->setDescription('重算佔成Min, Max欄位')
            ->setHelp(<<<EOT
重算佔成Min, Max欄位
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $curDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $curDate->format(\DateTime::ISO8601);
        $output->write("{$dateStr} : Begin updating sharelimit min max...", true);

        $this->handle('ShareLimit');
        $this->handle('ShareLimitNext');

        $endDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $endDate->format(\DateTime::ISO8601);
        $output->write("{$dateStr} : Finish updating sharelimit min max.", true);
    }

    /**
     * 更新佔成或預改佔成的min, max欄位
     *
     * @param String $entity
     */
    private function handle($entity)
    {
        $em = $this->getEntityManager();

        $this->output->write("Start handle $entity", true);

        $repo = $em->getRepository("BBDurianBundle:{$entity}");

        $offset = 0;
        while (1) {

            $shareArray = $repo->findBy(array(), array('id' => 'asc'), $this->limit, $offset);

            if (empty($shareArray)) {
                break;
            }

            foreach ($shareArray as $share) {
                $repo->updateMinMax($share);
            }

            $em->flush();

            $offset += $this->limit;
            $this->output->write("save $offset", true);

            $em->clear();
            unset($shareArray);
        }

        $this->output->write("Finish handle $entity", true);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->em) {
            return $this->em;
        }

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        return $this->em;
    }
}
