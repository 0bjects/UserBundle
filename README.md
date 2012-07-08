Installation instructions:

1.add this lines to your deps file:

[FacebookApiLibrary]
    git=http://github.com/facebook/php-sdk.git

[APIBundle]
    git=http://github.com/0bjects/APIBundle.git
    target=../src/Objects/APIBundle

[doctrine-fixtures]
    git=http://github.com/doctrine/data-fixtures.git

[DoctrineFixturesBundle]
    git=http://github.com/doctrine/DoctrineFixturesBundle.git
    target=bundles/Symfony/Bundle/DoctrineFixturesBundle
    version=origin/2.0

[UserBundle]
    git=http://github.com/0bjects/UserBundle.git
    target=../src/Objects/UserBundle

2.run bin/vendors update

3.add this line to your app/AppKernel.php :

new Symfony\Bundle\DoctrineFixturesBundle\DoctrineFixturesBundle(),
new Objects\APIBundle\ObjectsAPIBundle(),
new Objects\UserBundle\ObjectsUserBundle(),

4.add this line to the file app/autoload.php

'OAuth'            => __DIR__.'/../src/Objects/APIBundle/libraries/abraham',
'Doctrine\\Common\\DataFixtures' => __DIR__.'/../vendor/doctrine-fixtures/lib',


5.add the routes in your app/config/routing.yml:

ObjectsAPIBundle:
    resource: "@ObjectsAPIBundle/Resources/config/routing.yml"
    prefix:   /

ObjectsUserBundle:
    resource: "@ObjectsUserBundle/Resources/config/routing.yml"
    prefix:   /

6.enable the translation in your config.yml file :

framework:
    esi:             ~
    translator:      { fallback: %locale% }

7.copy the security.yml file into your app/config folder

8.update the database
app/console doctrine:schema:update --force

9.load the fixture files
app/console doctrine:fixtures:load --append

optional:

configure the parameters in Resources/config/config.yml file in the bundles

IMPORTANT NOTE:
***********************
remove the .git folder in src/Objects/APIBundle or in src/Objects/UserBundle
if you are going to make project specific changes
so that you do not push them to the bundle repo and remove the deps and deps.lock lines
***********************