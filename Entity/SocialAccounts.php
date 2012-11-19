<?php

namespace Objects\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Objects\UserBundle\Entity\SocialAccounts
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Objects\UserBundle\Entity\SocialAccountsRepository")
 * @author Mahmoud
 */
class SocialAccounts {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="\Objects\UserBundle\Entity\User", inversedBy="socialAccounts")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $user;

    /**
     * @var string $oauth_token
     *
     * @ORM\Column(name="oauth_token", type="string", length=255, nullable=true)
     */
    private $oauth_token;

    /**
     * @var string $oauth_token_secret
     *
     * @ORM\Column(name="oauth_token_secret", type="string", length=255, nullable=true)
     */
    private $oauth_token_secret;

    /**
     * @var string $linkedin_oauth_token
     *
     * @ORM\Column(name="linkedin_oauth_token", type="string", length=255, nullable=true)
     */
    private $linkedin_oauth_token;

    /**
     * @var string $linkedin_oauth_token_secret
     *
     * @ORM\Column(name="linkedin_oauth_token_secret", type="string", length=255, nullable=true)
     */
    private $linkedin_oauth_token_secret;

    /**
     * @var string $twitterId
     *
     * @ORM\Column(name="twitterId", type="string", length=255, nullable=true, unique=true)
     */
    private $twitterId;

    /**
     * @var string $linkedInId
     *
     * @ORM\Column(name="linkedInId", type="string", length=255, nullable=true, unique=true)
     */
    private $linkedInId;

    /**
     * @var boolean $postToLinkedIn
     *
     * @ORM\Column(name="postToLinkedIn", type="boolean")
     */
    private $postToLinkedIn = TRUE;

    /**
     * @var string $screenName
     *
     * @ORM\Column(name="screenName", type="string", length=255, nullable=true, unique=true)
     */
    private $screenName;

    /**
     * @var boolean $postToTwitter
     *
     * @ORM\Column(name="postToTwitter", type="boolean")
     */
    private $postToTwitter = TRUE;

    /**
     * @var integer $facebookId
     *
     * @ORM\Column(name="facebookId", type="string", length=255, nullable=true, unique=true)
     */
    private $facebookId;

    /**
     * @var string $access_token
     *
     * @ORM\Column(name="access_token", type="string", length=255, nullable=true)
     */
    private $access_token;

    /**
     * @var string $confirmationCode
     *
     * @ORM\Column(name="fb_tkn_expire_date", type="date", nullable=true)
     */
    private $fbTokenExpireDate;

    /**
     * @var boolean $postToFB
     *
     * @ORM\Column(name="postToFB", type="boolean")
     */
    private $postToFB = TRUE;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set oauth_token
     *
     * @param string $oauthToken
     */
    public function setOauthToken($oauthToken) {
        $this->oauth_token = $oauthToken;
    }

    /**
     * Get oauth_token
     *
     * @return string
     */
    public function getOauthToken() {
        return $this->oauth_token;
    }

    /**
     * Set oauth_token_secret
     *
     * @param string $oauthTokenSecret
     */
    public function setOauthTokenSecret($oauthTokenSecret) {
        $this->oauth_token_secret = $oauthTokenSecret;
    }

    /**
     * Get oauth_token_secret
     *
     * @return string
     */
    public function getOauthTokenSecret() {
        return $this->oauth_token_secret;
    }

    /**
     * Set twitterId
     *
     * @param string $twitterId
     */
    public function setTwitterId($twitterId) {
        $this->twitterId = $twitterId;
    }

    /**
     * Get twitterId
     *
     * @return string
     */
    public function getTwitterId() {
        return $this->twitterId;
    }

    /**
     * Set screenName
     *
     * @param string $screenName
     */
    public function setScreenName($screenName) {
        $this->screenName = $screenName;
    }

    /**
     * Get screenName
     *
     * @return string
     */
    public function getScreenName() {
        return $this->screenName;
    }

    /**
     * Set postToTwitter
     *
     * @param boolean $postToTwitter
     */
    public function setPostToTwitter($postToTwitter) {
        $this->postToTwitter = $postToTwitter;
    }

    /**
     * Get postToTwitter
     *
     * @return boolean
     */
    public function getPostToTwitter() {
        return $this->postToTwitter;
    }

    /**
     * Set facebookId
     *
     * @param string $facebookId
     */
    public function setFacebookId($facebookId) {
        $this->facebookId = $facebookId;
    }

    /**
     * Get facebookId
     *
     * @return string
     */
    public function getFacebookId() {
        return $this->facebookId;
    }

    /**
     * Set access_token
     *
     * @param string $accessToken
     */
    public function setAccessToken($accessToken) {
        $this->access_token = $accessToken;
    }

    /**
     * Get access_token
     *
     * @return string
     */
    public function getAccessToken() {
        return $this->access_token;
    }

    /**
     * Set postToFB
     *
     * @param boolean $postToFB
     */
    public function setPostToFB($postToFB) {
        $this->postToFB = $postToFB;
    }

    /**
     * Get postToFB
     *
     * @return boolean
     */
    public function getPostToFB() {
        return $this->postToFB;
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
     * Set fbTokenExpireDate
     *
     * @param date $fbTokenExpireDate
     */
    public function setFbTokenExpireDate($fbTokenExpireDate) {
        $this->fbTokenExpireDate = $fbTokenExpireDate;
    }

    /**
     * Get fbTokenExpireDate
     *
     * @return date
     */
    public function getFbTokenExpireDate() {
        return $this->fbTokenExpireDate;
    }

    /**
     * this function will check if the twitter account is linked
     * @return boolean true if the twitter account is linked
     */
    public function isTwitterLinked() {
        if ($this->getTwitterId()) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * this function will check if the facebook account is linked
     * @return boolean true if the facebook account is linked
     */
    public function isFacebookLinked() {
        if ($this->getFacebookId()) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * this function will check if the linkedin account is linked
     * @return boolean true if the linkedin account is linked
     */
    public function isLinkedInLinked() {
        if ($this->getLinkedInId()) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * this function will unlink the user twitter account data
     */
    public function unlinkTwitter() {
        $this->setOauthToken(NULL);
        $this->setOauthTokenSecret(NULL);
        $this->setScreenName(NULL);
        $this->setTwitterId(NULL);
    }

    /**
     * this function will unlink the user facebook account data
     */
    public function unlinkFacebook() {
        $this->setFacebookId(NULL);
        $this->setFbTokenExpireDate(NULL);
        $this->setAccessToken(NULL);
    }

    /**
     * this function will unlink the user linkedin account data
     */
    public function unlinkLinkedIn() {
        $this->setLinkedinOauthToken(NULL);
        $this->setLinkedinOauthTokenSecret(NULL);
        $this->setLinkedInId(NULL);
    }

    /**
     * this function will check if we still need this object in the database
     * @return boolean true if the object has any linked social accounts
     */
    public function isNeeded() {
        if ($this->isFacebookLinked() || $this->isTwitterLinked() || $this->isLinkedInLinked()) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Set linkedInId
     *
     * @param string $linkedInId
     */
    public function setLinkedInId($linkedInId) {
        $this->linkedInId = $linkedInId;
    }

    /**
     * Get linkedInId
     *
     * @return string
     */
    public function getLinkedInId() {
        return $this->linkedInId;
    }

    /**
     * Set postToLinkedIn
     *
     * @param boolean $postToLinkedIn
     */
    public function setPostToLinkedIn($postToLinkedIn) {
        $this->postToLinkedIn = $postToLinkedIn;
    }

    /**
     * Get postToLinkedIn
     *
     * @return boolean
     */
    public function getPostToLinkedIn() {
        return $this->postToLinkedIn;
    }

    /**
     * Set linkedin_oauth_token
     *
     * @param string $linkedinOauthToken
     */
    public function setLinkedinOauthToken($linkedinOauthToken) {
        $this->linkedin_oauth_token = $linkedinOauthToken;
    }

    /**
     * Get linkedin_oauth_token
     *
     * @return string 
     */
    public function getLinkedinOauthToken() {
        return $this->linkedin_oauth_token;
    }

    /**
     * Set linkedin_oauth_token_secret
     *
     * @param string $linkedinOauthTokenSecret
     */
    public function setLinkedinOauthTokenSecret($linkedinOauthTokenSecret) {
        $this->linkedin_oauth_token_secret = $linkedinOauthTokenSecret;
    }

    /**
     * Get linkedin_oauth_token_secret
     *
     * @return string 
     */
    public function getLinkedinOauthTokenSecret() {
        return $this->linkedin_oauth_token_secret;
    }

}