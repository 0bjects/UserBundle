<?php

/**
 * @author mahmoud
 */

namespace Objects\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Objects\UserBundle\Entity\User;
use Objects\UserBundle\Entity\Role;

class LoadUserData implements FixtureInterface {

    public function load(ObjectManager $manager) {
        // create the ROLE_ADMIN role
        $roleAdmin = new Role();
        $roleAdmin->setName('ROLE_ADMIN');
        $manager->persist($roleAdmin);

        // create the ROLE_NOTACTIVE role
        $roleNotActive = new Role();
        $roleNotActive->setName('ROLE_NOTACTIVE');
        $manager->persist($roleNotActive);

        // create the ROLE_UPDATABLE_USERNAME role
        $roleUserName = new Role();
        $roleUserName->setName('ROLE_UPDATABLE_USERNAME');
        $manager->persist($roleUserName);

        // create the ROLE_USER role
        $roleUser = new Role();
        $roleUser->setName('ROLE_USER');
        $manager->persist($roleUser);


        // create admin user
        $user1 = new User();
        $user1->setLoginName('Objects');
        $user1->setPassword('0bjects123');
        $user1->setEmail('objects@objects.ws');
        $user1->hashPassword();
        $user1->getUserRoles()->add($roleAdmin);
        $manager->persist($user1);
    }

}