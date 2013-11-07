Installation instructions:

1.add this lines to your composer.json file in "require" part:
"doctrine/doctrine-fixtures-bundle": "dev-master"

2.add this lines to your app/AppKernel.php :

new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
new Objects\UserBundle\ObjectsUserBundle(),

3.add the routes in your app/config/routing.yml:

ObjectsUserBundle:
    resource: "@ObjectsUserBundle/Resources/config/routing.yml"
    prefix:   /

4.enable the translation in your config.yml file :

framework:
    esi:             ~
    translator:      { fallback: %locale% }

5.copy the security.yml file into your app/config folder

6.run composer update

7.update the database
app/console doctrine:schema:update --force

8.load the fixture files
app/console doctrine:fixtures:load --append

optional:

configure the parameters in Resources/config/config.yml file in the bundles
enable the last seen listener in Resources/config/services.yml
install api bundle if you want the social sites login and signup