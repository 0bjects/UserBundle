<?php

namespace Objects\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Objects\UserBundle\Entity\Report
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Objects\UserBundle\Entity\ReportRepository")
 * @author Mahmoud
 */
class Report {

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
     * @ORM\ManyToOne(targetEntity="\Objects\UserBundle\Entity\User", inversedBy="reportUsers")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", onUpdate="CASCADE", nullable=false)
     */
    private $user;

    /**
     * @var integer $blockedUser
     * 
     * @ORM\ManyToOne(targetEntity="\Objects\UserBundle\Entity\User", inversedBy="reoprtedMe")
     * @ORM\JoinColumn(name="reported_user_id", referencedColumnName="id", onDelete="CASCADE", onUpdate="CASCADE", nullable=false)
     */
    private $reportedUser;

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
     * Set reportedUser
     *
     * @param Objects\UserBundle\Entity\User $reportedUser
     */
    public function setReportedUser(\Objects\UserBundle\Entity\User $reportedUser) {
        $this->reportedUser = $reportedUser;
    }

    /**
     * Get reportedUser
     *
     * @return Objects\UserBundle\Entity\User 
     */
    public function getReportedUser() {
        return $this->reportedUser;
    }

}