<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserDetail;

class LoadUserDetailData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //tester
        $user = $manager->find('BB\DurianBundle\Entity\User', 8);

        $detail = new UserDetail($user);

        $detail->setNickname('MJ149')
               ->setNameReal('達文西')
               ->setNameChinese('甲級情報員')
               ->setNameEnglish('Da Vinci')
               ->setCountry('Republic of China')
               ->setPassport('PA123456')
               ->setIdentityCard('')
               ->setDriverLicense('')
               ->setInsuranceCard('')
               ->setHealthCard('')
               ->setTelephone('3345678')
               ->setQQNum('485163154787')
               ->setNote('Hello Durian')
               ->setPassword('')
               ->setWechat('abcde123');

        $birth = new \DateTime('2000-10-10');
        $detail->setBirthday($birth);
        $manager->persist($detail);

        //gaga
        $user = $manager->find('BB\DurianBundle\Entity\User', 10);

        $detail = new UserDetail($user);

        $detail->setNickname('柯P')
               ->setNameReal('叮叮說你好')
               ->setNameChinese('真是個人才')
               ->setNameEnglish('Din Din')
               ->setCountry('Republic of Idiot')
               ->setPassport('')
               ->setIdentityCard('IC654321')
               ->setDriverLicense('')
               ->setInsuranceCard('')
               ->setHealthCard('')
               ->setTelephone('33456785')
               ->setQQNum('485163154767')
               ->setNote('Hello Din Din')
               ->setPassword('')
               ->setWechat('abcde123');

        $birth = new \DateTime('2000-10-10');
        $detail->setBirthday($birth);
        $manager->persist($detail);

        $user = $manager->find('BBDurianBundle:User', 6);
        $detail = new UserDetail($user);

        $detail->setNickname('黑心油')
               ->setNameReal('刪除測試')
               ->setNameChinese('測試刪除')
               ->setNameEnglish('Din Din')
               ->setCountry('Republic of Idiot')
               ->setPassport('')
               ->setIdentityCard('')
               ->setDriverLicense('DL654321')
               ->setInsuranceCard('')
               ->setHealthCard('')
               ->setTelephone('33456785')
               ->setQQNum('485163154767')
               ->setNote('Hello Din Din')
               ->setPassword('')
               ->setWechat('abcde123');

        $birth = new \DateTime('2000-10-10');
        $detail->setBirthday($birth);
        $manager->persist($detail);

        $user2 = $manager->find('BBDurianBundle:User', 2);
        $detail2 = new UserDetail($user2);
        $manager->persist($detail2);

        $user3 = $manager->find('BBDurianBundle:User', 3);
        $detail3 = new UserDetail($user3);
        $manager->persist($detail3);

        $user4 = $manager->find('BBDurianBundle:User', 4);
        $detail4 = new UserDetail($user4);
        $manager->persist($detail4);

        $user5 = $manager->find('BBDurianBundle:User', 5);
        $detail5 = new UserDetail($user5);
        $manager->persist($detail5);

        $user7 = $manager->find('BBDurianBundle:User', 7);
        $detail7 = new UserDetail($user7);
        $manager->persist($detail7);

        $user9 = $manager->find('BBDurianBundle:User', 9);
        $detail9 = new UserDetail($user9);
        $manager->persist($detail9);

        $user50 = $manager->find('BBDurianBundle:User', 50);
        $detail50 = new UserDetail($user50);
        $manager->persist($detail50);

        $user51 = $manager->find('BBDurianBundle:User', 51);
        $detail51 = new UserDetail($user51);
        $manager->persist($detail51);

        $user20m = $manager->find('BBDurianBundle:User', 20000000);
        $detail20m = new UserDetail($user20m);
        $manager->persist($detail20m);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        );
    }
}
