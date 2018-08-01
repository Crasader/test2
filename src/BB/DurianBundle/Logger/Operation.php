<?php
namespace BB\DurianBundle\Logger;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\LogOperation;

/**
 * 操作紀錄
 */
class Operation extends ContainerAware
{
    /**
     * 新增操作紀錄
     *
     * @param string $tableName 資料表名稱
     * @param array $majorKeys 主要的欄位
     * @return LogOperation
     */
    public function create($tableName, $majorKeys)
    {
        $request = $this->container->get('request', null);

        $majorKey = [];
        foreach ($majorKeys as $key => $value) {
            $majorKey[] = "@$key:$value";
        }
        $majorKey = implode(', ', $majorKey);

        if ($request) {
            $uri = $request->getRequestUri();
            $method = $request->getMethod();
            $serverName = gethostname();
            $clientIp = $request->getClientIp();
            $sessionId = trim($request->headers->get('session-id'));
        } else {
            // command呼叫需先設定 $this->getContainer()->set('durian.command', $this);
            $uri = $this->container->get('durian.command')->getName();
            $method = 'CMD';
            $serverName = gethostname();
            $clientIp = '127.0.0.1';
            $sessionId = '';
        }

        return new LogOperation(
            $uri,
            $method,
            $serverName,
            $clientIp,
            '',
            $tableName,
            $majorKey,
            $sessionId
        );
    }

    /**
     * 儲存操作紀錄
     *
     * @param LogOperation $log
     */
    public function save($log)
    {
        $em = $this->getEntityManager('share');
        $em->persist($log);
    }

    /**
     * 回傳 EntityManager
     *
     * @param string $name EntityManager名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->container->get('doctrine')->getManager($name);
    }
}
