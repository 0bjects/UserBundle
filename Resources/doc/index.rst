Installation instructions:

add this lines to your deps file:

[FacebookApiLibrary]
    git=http://github.com/facebook/php-sdk.git

[APIBundle]
    git=repo@184.107.198.186:/home/repos/APIBundle.git
    target=../src/Objects/APIBundle

[doctrine-fixtures]
    git=http://github.com/doctrine/data-fixtures.git

[DoctrineFixturesBundle]
    git=http://github.com/symfony/DoctrineFixturesBundle.git
    target=bundles/Symfony/Bundle/DoctrineFixturesBundle

[UserBundle]
    git=repo@184.107.198.186:/home/repos/UserBundle.git
    target=../src/Objects/UserBundle

*******************************************************************
run bin/vendors update you will be asked about the password 4 times
*******************************************************************

add this line to your app/AppKernel.php :

new Symfony\Bundle\DoctrineFixturesBundle\DoctrineFixturesBundle(),
new Objects\APIBundle\ObjectsAPIBundle(),
new Objects\UserBundle\ObjectsUserBundle(),

add this line to the file app/autoload.php

'OAuth'            => __DIR__.'/../src/Objects/APIBundle/libraries/abraham',
'Doctrine\\Common\\DataFixtures' => __DIR__.'/../vendor/doctrine-fixtures/lib',


add the routes in your app/config/routing.yml:

ObjectsAPIBundle:
    resource: "@ObjectsAPIBundle/Resources/config/routing.yml"
    prefix:   /

ObjectsUserBundle:
    resource: "@ObjectsUserBundle/Resources/config/routing.yml"
    prefix:   /

enable the translation in your config.yml file :

framework:
    esi:             ~
    translator:      { fallback: %locale% }

configure the parameters in Resources/config/config.yml file in the bundles

IMPORTANT NOTE:
***********************
remove the .git folder in src/Objects/APIBundle or in src/Objects/UserBundle
if you are going to make project specific changes
so that you do not push them to the bundle repo and remove the deps and deps.lock lines
***********************