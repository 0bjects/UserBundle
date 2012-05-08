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
        $user1->setUserName('mahmoud');
        $user1->setPassword('123');
        $user1->setEmail('mahmoud@objects.ws');
        $user1->hashPassword();
        $user1->getUserRoles()->add($roleAdmin);
        $manager->persist($user1);

        // create active user
        $user2 = new User();
        $user2->setUserName('Ahmed');
        $user2->setPassword('123');
        $user2->setEmail('ahmed@objects.ws');
        $user2->hashPassword();
        $user2->getUserRoles()->add($roleUser);
        $manager->persist($user2);


        //create a user 
        $user3 = new User();
        $user3->setUserName('mirehan');
        $user3->setPassword('123');
        $user3->setEmail('mirehan@objects.ws');
        $user3->hashPassword();
        $user3->getUserRoles()->add($roleUser);

        $manager->persist($user3);

        //create a user that can update username
        $user4 = new User();
        $user4->setUserName('sammer');
        $user4->setPassword('123');
        $user4->setEmail('sammer@objects.ws');
        $user4->hashPassword();
        $user4->getUserRoles()->add($roleUser);
        $user4->getUserRoles()->add($roleUserName);
        $manager->persist($user4);

        //create a NotActivated user 
        $user5 = new User();
        $user5->setUserName('notactive');
        $user5->setPassword('123');
        $user5->setEmail('notactive@objects.ws');
        $user5->hashPassword();
        $user5->getUserRoles()->add($roleNotActive);
        $manager->persist($user5);
        $manager->flush();
    }

}