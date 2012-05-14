<?php

namespace Objects\UserBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository implements UserProviderInterface {

    /**
     * implementation of loadUserByUsername for UserProviderInterface
     * @param type $username
     * @return type
     * @throws UsernameNotFoundException 
     */
    public function loadUserByUsername($username) {
        $q = $this
                ->createQueryBuilder('u')
                ->where('u.loginName = :username OR u.email = :email')
                ->setParameter('username', $username)
                ->setParameter('email', $username)
                ->getQuery()
        ;
        try {
            // The Query::getSingleResult() method throws an exception
            // if there is no record matching the criteria.
            $user = $q->getSingleResult();
        } catch (NoResultException $e) {
            throw new UsernameNotFoundException(sprintf('Unable to find the specified user: "%s"', $username), null, 0, $e);
        }
        return $user;
    }

    /**
     * implementation of refreshUser for UserProviderInterface
     * @param UserInterface $user
     * @return type
     * @throws UnsupportedUserException 
     */
    public function refreshUser(UserInterface $user) {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * implementation of supportsClass for UserProviderInterface
     * @param type $class
     * @return type 
     */
    public function supportsClass($class) {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }

    /**
     * this function will try to return a user login name that does not exist in our database
     * @author Alshimaa edited by Mahmoud
     * @param string $loginName
     * @return string a valid unique login name
     */
    public function getValidLoginName($loginName) {
        $query = $this->getEntityManager()
                ->createQuery('
                     SELECT max(SUBSTRING(u.loginName, :start)) as offset
                     FROM Objects\UserBundle\Entity\User u
                     WHERE u.loginName like :loginName
                    ');
        $query->setParameter('start', strlen($loginName) + 1);
        $query->setParameter('loginName', $loginName . '%');
        $result = $query->getResult();
        $offset = $result[0]['offset'] + 1;
        return $loginName . $offset;
    }

}
