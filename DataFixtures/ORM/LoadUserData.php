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
        // load the main required roles

        // create the ROLE_ADMIN role
        $roleAdmin = new Role();
        $roleAdmin->setName('ROLE_ADMIN');
        $manager->persist($roleAdmin);

        // create the ROLE_NOTACTIVE role
        $roleNotActive = new Role();
        $roleNotActive->setName('ROLE_NOTACTIVE');
        $manager->persist($roleNotActive);

        // create the ROLE_USER role
        $roleUser = new Role();
        $roleUser->setName('ROLE_USER');
        $manager->persist($roleUser);

        // create the ROLE_UPDATABLE_USERNAME role
        $roleUserName = new Role();
        $roleUserName->setName('ROLE_UPDATABLE_USERNAME');
        $manager->persist($roleUserName);

        // create admin user
        $user = new User();
        $user->setLoginName('Objects');
        $user->setUserPassword('0bjects123');
        $user->setEmail('objects@objects.ws');
        $user->setFirstName('Objects');
        $user->getUserRoles()->add($roleAdmin);
        $manager->persist($user);

        // create admin user
        $user1 = new User();
        $user1->setLoginName('mahmoud');
        $user1->setUserPassword('123');
        $user1->setEmail('mahmoud@objects.ws');
        $user1->setFirstName('mahmoud');
        $user1->getUserRoles()->add($roleAdmin);
        $manager->persist($user1);

        // create active user
        $user2 = new User();
        $user2->setLoginName('Ahmed');
        $user2->setUserPassword('123');
        $user2->setEmail('ahmed@objects.ws');
        $user2->setFirstName('Ahmed');
        $user2->getUserRoles()->add($roleUser);
        $manager->persist($user2);


        //create a user
        $user3 = new User();
        $user3->setLoginName('mirehan');
        $user3->setUserPassword('123');
        $user3->setEmail('mirehan@objects.ws');
        $user3->setFirstName('mirehan');
        $user3->getUserRoles()->add($roleUser);
        $user3->getUserRoles()->add($roleUserName);
        $manager->persist($user3);

        //create a NotActivated user
        $user4 = new User();
        $user4->setLoginName('notactive');
        $user4->setUserPassword('123');
        $user4->setEmail('notactive@objects.ws');
        $user4->setFirstName('notactive');
        $user4->getUserRoles()->add($roleNotActive);
        $manager->persist($user4);

        $manager->flush();
    }

}