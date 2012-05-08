<?php

namespace Objects\UserBundle\Entity;

use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Objects\UserBundle\Entity\User
 * 
 * @UniqueEntity(fields={"loginName"}, groups={"userName","registration","adminUser","editUsrName","MobileRegistration"})
 * @UniqueEntity(fields={"email"}, groups={"email","registration","adminUser","adminEditUser","editemail","MobileRegistration"})
 * @ORM\Table(indexes={@ORM\Index(name="search_user_name", columns={"loginName"})})
 * @ORM\Entity(repositoryClass="Objects\UserBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @author Mahmoud
 */
class User implements AdvancedUserInterface {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="\Objects\UserBundle\Entity\Report", mappedBy="user",cascade={"persist","remove"})
     */
    private $reportedUsers;

    /**
     * @ORM\OneToMany(targetEntity="\Objects\UserBundle\Entity\Report", mappedBy="reportedUser",cascade={"persist","remove"})
     */
    private $reoprtedMe;

    /**
     * @ORM\ManyToMany(targetEntity="\Objects\UserBundle\Entity\Role")
     * @ORM\JoinTable(name="user_role",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", onUpdate="CASCADE", nullable=false)},
     *     inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE", onUpdate="CASCADE", nullable=false)}
     * )
     *
     * @var ArrayCollection $userRoles
     */
    protected $userRoles;

    /**
     * @ORM\OneToOne(targetEntity="\Objects\UserBundle\Entity\socialAccounts", mappedBy="user", fetch="EAGER",cascade={"remove","update","persist"})
     */
    private $socialAccounts;

    /**
     * @ORM\OneToMany(targetEntity="\Objects\UserBundle\Entity\Blocked", mappedBy="user")
     */
    private $blockedUsers;

    /**
     * @ORM\OneToMany(targetEntity="\Objects\UserBundle\Entity\Blocked", mappedBy="blockedUser")
     */
    private $blockedMe;

    /**
     * @var string $loginName
     *
     * @ORM\Column(name="loginName", type="string", length=255, nullable=true, unique=true)
     * @Assert\NotBlank(groups={"userName","registration","editUsrName","MobileRegistration"})
     */
    private $loginName;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true, nullable=true)
     * @Assert\NotBlank(groups={"registration","editemail","adminUser","adminEditUser","MobileRegistration"})
     * @Assert\Email(groups={"email","registration","editemail","adminUser","adminEditUser","MobileRegistration"})
     */
    private $email;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=255)
     * @Assert\NotBlank(groups={"registration","adminUser","MobileRegistration"})
     * @Assert\MinLength(limit=6, groups={"registration","adminUser","MobileRegistration"},message = "must be 6 characters at least.")
     */
    private $password;

    /**
     * @var string $confirmationCode
     *
     * @ORM\Column(name="confirmationCode", type="string", length=64)
     * @Assert\NotBlank
     */
    private $confirmationCode;

    /**
     * @var date $created_at
     *
     * @ORM\Column(name="createdAt", type="date")
     */
    private $createdAt;

    /**
     * @var datetime $lastLoginDate
     *
     * @ORM\Column(name="lastLoginDate", type="datetime")
     */
    private $lastLoginDateTime;

    /**
     * @var string $firstName
     *
     * @ORM\Column(name="firstName", type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @var string $lastName
     *
     * @ORM\Column(name="lastName", type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @var text $about
     *
     * @ORM\Column(name="about", type="text", nullable=true)
     */
    private $about;

    /**
     * @var boolean $gender
     * 0 female, 1 male
     * @ORM\Column(name="gender", type="boolean", nullable=true)
     */
    private $gender;

    /**
     * @var date $dateOfBirth
     *
     * @ORM\Column(name="dateOfBirth", type="date", nullable=true)
     */
    private $dateOfBirth;

    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     * Assert\Url
     */
    private $url;

    /**
     * @var string $countryCode
     * 
     * @ORM\Column(name="country_code", type="string", length=2, nullable=true)
     */
    private $countryCode;

    /**
     * @var string $suggestedLanguage
     * 
     * @ORM\Column(name="suggested_language", type="string", length=2, nullable=true)
     */
    private $suggestedLanguage = 'en';

    /**
     * @var boolean $locked
     * @ORM\Column(name="locked", type="boolean")
     */
    private $locked = FALSE;

    /**
     * @var boolean $enabled
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled = TRUE;

    /**
     * @ORM\Column(type="string", length="255")
     *
     * @var string salt
     */
    protected $salt;

    /**
     * @var string $image
     *
     * @ORM\Column(name="image", type="string", length=20, nullable=true)
     */
    private $image;

    /**
     * a temp variable for storing the old image name to delete the old image after the update
     * @var string $temp
     */
    private $temp;

    /**
     * this flag is for detecting if the image has already been uploaded
     * @var boolean $uploaded
     */
    private $uploaded = FALSE;

    /**
     * @Assert\Image
     * @var \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public $file;

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload() {
        if (NULL === $this->file || $this->uploaded) {
            return;
        }
        //get the image extension
        $extension = $this->file->guessExtension();
        //generate a random image name
        $img = uniqid();
        //check that this name does not exist
        while (@file_exists($this->getUploadRootDir() . "/$img.$extension")) {
            //try to find a new unique name
            $img = uniqid();
        }
        //check if we have an old image
        if ($this->image) {
            //store the old name to delete the image on the upadate
            $this->temp = $this->image;
        }
        //set the image new name
        $this->image = "$img.$extension";
        //set the flag to indecate that the image is uploaded
        $this->uploaded = TRUE;
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload() {
        if (NULL === $this->file) {
            return;
        }
        // you must throw an exception here if the file cannot be moved
        // so that the entity is not persisted to the database
        // which the UploadedFile move() method does
        $this->file->move($this->getUploadRootDir(), $this->image);
        //remove the file as you do not need it any more
        $this->file = NULL;
        //check if we have an old image
        if ($this->temp) {
            //try to delete the old image
            @unlink($this->getUploadRootDir() . '/' . $this->temp);
        }
    }

    /**
     * @ORM\PostRemove()
     */
    public function postRemove() {
        //check if we have an image
        if ($this->image) {
            //try to delete the image
            @unlink($this->getAbsolutePath());
        }
    }

    /**
     * @return string the path of image starting of root
     */
    private function getAbsolutePath() {
        return $this->getUploadRootDir() . '/' . $this->image;
    }

    /**
     * @return string the relative path of image starting from web directory 
     */
    public function getWebPath() {
        return NULL === $this->image ? NULL : '/' . $this->getUploadDir() . '/' . $this->image;
    }

    /**
     * @return string the path of upload directory starting of root
     */
    private function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    /**
     * @author Mahmoud
     * @param $width the desired image width
     * @param $height the desired image height
     * @return string the htaccess file url pattern which map to timthumb url
     */
    public function getTimThumbUrl($width = 50, $height = 50) {
        return NULL === $this->image ? NULL : "/user-profile-image/$width/$height/$this->image";
    }

    /**
     * @return string the document upload directory path starting from web folder
     */
    private function getUploadDir() {
        return 'images/users-profiles-images';
    }

    public function __construct() {
        $this->createdAt = new \DateTime();
        $this->lastLoginDateTime = new \DateTime();
        $this->confirmationCode = md5(uniqid(rand()));
        $this->salt = md5(time());
        $this->password = rand();
        $this->userRoles = new ArrayCollection();
        $this->blockedUsers = new ArrayCollection();
        $this->blockedMe = new ArrayCollection();
        $this->reportedUsers = new ArrayCollection();
        $this->reoprtedMe = new ArrayCollection();
    }

    public function __toString() {
        if ($this->firstName) {
            if ($this->lastName) {
                return "$this->firstName $this->lastName";
            } else {
                return $this->firstName;
            }
        } else {
            if ($this->loginName) {
                return $this->loginName;
            } else {
                return $this->email;
            }
        }
    }

    /**
     * @return string the site map xml files folder name it has to be the same
     * as the show user profile route prefix
     */
    public function getSiteMapWebFolder() {
        return 'user';
    }

    /**
     * be sure to call this method when you set the password from the user and before storing to database
     * the encoding has to be the same as the one in the project security.yml file
     */
    public function hashPassword() {
        // encode and set the password for the user
        $encoder = new MessageDigestPasswordEncoder('sha512', true, 10);
        $this->setPassword($encoder->encodePassword($this->getPassword(), $this->getSalt()));
    }

    /**
     * Implementation of getRoles for the UserInterface.
     * 
     * @return array An array of Roles
     */
    public function getRoles() {
        return $this->getUserRoles()->toArray();
    }

    /**
     * Implementation of eraseCredentials for the UserInterface.
     */
    public function eraseCredentials() {
        
    }

    /**
     * Implementation of equals for the UserInterface.
     * Compares this user to another to determine if they are the same.
     * @param UserInterface $user The user to compare with this user
     * @return boolean True if equal, false othwerwise.
     */
    public function equals(UserInterface $user) {
        return md5($this->getUserName()) == md5($user->getUserName());
    }

    /**
     * Implementation of getPassword for the UserInterface.
     * @return string the hashed user password
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Implementation of getSalt for the UserInterface.
     * @return string the user salt
     */
    public function getSalt() {
        return $this->salt;
    }

    /**
     * Implementation of getUsername for the UserInterface.
     * check security.yml to know the used column by the firewall
     * @return string the user name used by the firewall configurations.
     */
    public function getUsername() {
        if ($this->loginName) {
            return $this->loginName;
        } else {
            return $this->email;
        }
    }

    /**
     * Implementation of isAccountNonExpired for the AdvancedUserInterface.
     * @return boolean
     */
    public function isAccountNonExpired() {
        return TRUE;
    }

    /**
     * Implementation of isCredentialsNonExpired for the AdvancedUserInterface.
     * @return boolean
     */
    public function isCredentialsNonExpired() {
        return TRUE;
    }

    /**
     * Implementation of isAccountNonLocked for the AdvancedUserInterface.
     * @return boolean
     */
    public function isAccountNonLocked() {
        return !$this->locked;
    }

    /**
     * Implementation of isEnabled for the AdvancedUserInterface.
     * @return boolean
     */
    public function isEnabled() {
        return $this->enabled;
    }

}