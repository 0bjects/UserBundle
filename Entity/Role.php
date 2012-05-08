<?php

namespace Objects\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Objects\UserBundle\Entity\Role
 *
 * @UniqueEntity(fields={"name"})
 * @ORM\Table
 * @ORM\Entity
 * @author Mahmoud
 */
class Role implements RoleInterface {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=30, unique=true)
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var text $description
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="\Objects\UserBundle\Entity\User", mappedBy="userRoles")
     *
     * @var ArrayCollection $userRoles
     */
    private $roleUsers;

    public function __toString() {
        return $this->getName();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text 
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Implementation of getRole for the RoleInterface.
     * 
     * @return string The role.
     */
    public function getRole() {
        return $this->getName();
    }

    public function __construct() {
        $this->roleUsers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get userRole
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRoleUsers() {
        return $this->roleUsers;
    }

    /**
     * Add roleUsers
     *
     * @param Objects\UserBundle\Entity\User $roleUsers
     */
    public function addUser(\Objects\UserBundle\Entity\User $roleUsers) {
        $this->roleUsers[] = $roleUsers;
    }

}