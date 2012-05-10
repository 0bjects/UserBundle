<?php

namespace Objects\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Objects\UserBundle\Entity\Blocked
 * 
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="user_block", columns={"user_id", "blockedUser_id"})})
 * @ORM\Entity(repositoryClass="Objects\UserBundle\Entity\BlockedRepository")
 * @author Mahmoud
 */
class Blocked {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer $User
     * 
     * @ORM\ManyToOne(targetEntity="\Objects\UserBundle\Entity\User", inversedBy="blockUsers")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", onUpdate="CASCADE", nullable=false)
     */
    private $user;

    /**
     * @var integer $blockedUser
     * 
     * @ORM\ManyToOne(targetEntity="\Objects\UserBundle\Entity\User", inversedBy="blockedMe")
     * @ORM\JoinColumn(name="blockedUser_id", referencedColumnName="id", onDelete="CASCADE", onUpdate="CASCADE", nullable=false)
     */
    private $blockedUser;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param Objects\UserBundle\Entity\User $user
     */
    public function setUser(\Objects\UserBundle\Entity\User $user) {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return Objects\UserBundle\Entity\User 
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Set blockedUser
     *
     * @param Objects\UserBundle\Entity\User $blockedUser
     */
    public function setBlockedUser(\Objects\UserBundle\Entity\User $blockedUser) {
        $this->blockedUser = $blockedUser;
    }

    /**
     * Get blockedUser
     *
     * @return Objects\UserBundle\Entity\User 
     */
    public function getBlockedUser() {
        return $this->blockedUser;
    }

}