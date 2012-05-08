<?php

namespace Objects\UserBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Objects\UserBundle\Entity\SocialAccounts
 *
 * @UniqueEntity(fields={"twitterId"}, groups={"twitter_id"})
 * @UniqueEntity(fields={"facebookId"}, groups={"facebook_id"})
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Objects\UserBundle\Entity\SocialAccountsRepository")
 */
class SocialAccounts {

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="\Objects\UserBundle\Entity\User", inversedBy="twitter", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", onUpdate="CASCADE", nullable=false)
     */
    private $user;

    /**
     * @var string $oauth_token
     *
     * @ORM\Column(name="oauth_token", type="string", length=255)
     * @Assert\NotBlank
     */
    private $oauth_token;

    /**
     * @var string $oauth_token_secret
     *
     * @ORM\Column(name="oauth_token_secret", type="string", length=255)
     * @Assert\NotBlank
     */
    private $oauth_token_secret;

    /**
     * @var string $twitterId
     *
     * @ORM\Column(name="twitterId", type="string", length=255, unique=true)
     * @Assert\NotBlank(groups={"twitter_id"})
     */
    private $twitterId;

    /**
     * @var string $screenName
     *
     * @ORM\Column(name="screenName", type="string", length=255, unique=true)
     * @Assert\NotBlank
     */
    private $screenName;

    /**
     * @var boolean $postToTwitter
     *
     * @ORM\Column(name="postToTwitter", type="boolean")
     * @Assert\NotBlank
     */
    private $postToTwitter = TRUE;

    /**
     * @var integer $facebookId
     *
     * @ORM\Column(name="facebookId", type="string", length=255)
     * @Assert\NotBlank
     */
    private $facebookId;
    
    /**
     * @var string $access_token
     *
     * @ORM\Column(name="access_token", type="string", length=255)
     * @Assert\NotBlank
     */
    private $access_token;

    /**
     * @var boolean $postToFB
     *
     * @ORM\Column(name="postToFB", type="boolean")
     * @Assert\NotBlank
     */
    private $postToFB = TRUE;


}