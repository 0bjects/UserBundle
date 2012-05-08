<?php

namespace Objects\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Objects\UserBundle\Entity\Report
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Objects\UserBundle\Entity\ReportRepository")
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

}