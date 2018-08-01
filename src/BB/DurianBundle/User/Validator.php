<?php

namespace BB\DurianBundle\User;

use BB\DurianBundle\Entity\User;

class Validator
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }

    /**
     * 驗證全部欄位
     *
     * @param User $user
     */
    public function validate(User $user)
    {
        $this->validateUsername($user->getUsername());
        $this->validatePassword($user->getPassword());
        $this->validateAlias($user->getAlias());
    }

    /**
     * 驗證使用者名稱可用
     *
     * @param string $user
     */
    public function validateUsername($username)
    {
        $len = mb_strlen($username, 'UTF-8');
        $max = User::MAX_USERNAME_LENGTH;
        $min = User::MIN_USERNAME_LENGTH;

        if ($len > $max || $len < $min) {
            throw new \InvalidArgumentException('Invalid username length given', 150010012);
        }

        if (!preg_match('/^[a-z0-9]*$/', $username)) {
            throw new \InvalidArgumentException('Invalid username character given', 150010013);
        }
    }

    /**
     * 驗證email可用
     *
     * @param string $email
     */
    public function validateEmail($email)
    {
        $em = $this->getEntityManager();
        $metadata = $em->getClassMetadata('BBDurianBundle:UserEmail');

        $emailFieldData = $metadata->getFieldMapping('email');
        $maxLength = $emailFieldData['length'];
        $len = mb_strlen($email, 'UTF-8');

        if ($len > $maxLength) {
            throw new \InvalidArgumentException('Invalid email length given', 150010146);
        }

        if (!preg_match('/^[A-Za-z0-9\.\-\_]+@[A-Za-z0-9\.\-]+\.[A-Za-z]+$/', $email)) {
            throw new \InvalidArgumentException('Invalid email given', 150010127);
        }
    }

    /**
     * 驗證帳號唯一性
     *
     * @param string  $username
     * @param integer $domain
     */
    public function validateUniqueUsername($username, $domain)
    {
        $repo = $this->getEntityManager()
                     ->getRepository('BBDurianBundle:User');

        $criteria = array(
            'username' => $username,
            'domain'   => $domain
        );

        $result = $repo->findBy($criteria);

        if (count($result) != 0) {
            throw new \RuntimeException('Username already exist', 150010014);
        }
    }

    /**
     * 驗證密碼可用
     *
     * @param string $password
     */
    public function validatePassword($password)
    {
        $len = strlen($password);
        $max = User::MAX_PASSWORD_LENGTH;
        $min = User::MIN_PASSWORD_LENGTH;

        if ($len > $max || $len < $min) {
            throw new \InvalidArgumentException('Invalid password length given', 150010015);
        }

        if (!preg_match('/^[a-z0-9\.\_\-\!\@\#\$\&\*\+\=\|]*$/', $password)) {
            throw new \InvalidArgumentException('Invalid password character given', 150010016);
        }
    }

    /**
     * 驗證暱稱可用
     *
     * @param string $alias
     */
    public static function validateAlias($alias)
    {
        $len = mb_strlen($alias, 'UTF-8');
        $max = User::MAX_ALIAS_LENGTH;
        $min = User::MIN_ALIAS_LENGTH;

        if ($len > $max || $len < $min) {
            throw new \InvalidArgumentException('Invalid alias length given', 150010017);
        }
        if (preg_match('/[><\'"=]/', $alias)) {
            throw new \InvalidArgumentException('Invalid alias character given', 150010018);
        }
    }
}
