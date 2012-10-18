Installation instructions:

1.add this lines to your composer.json file in "require" part:
"facebook/php-sdk": "dev-master",
"doctrine/doctrine-fixtures-bundle": "dev-master"

2.run composer update

3.add this lines to your app/AppKernel.php :

new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
new Objects\APIBundle\ObjectsAPIBundle(),
new Objects\UserBundle\ObjectsUserBundle(),

4.add this line to the file app/autoload.php after "$loader = require __DIR__.'/../vendor/autoload.php';"
$loader->add('OAuth', __DIR__.'/../src/Objects/APIBundle/libraries/abraham');

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
***********************