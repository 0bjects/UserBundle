<?php

namespace Objects\UserBundle\Entity;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Objects\UserBundle\Entity\User
 *
 * @UniqueEntity(fields={"loginName"}, groups={"loginName"})
 * @UniqueEntity(fields={"email"}, groups={"signup", "edit", "email"})
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
     * @ORM\OneToOne(targetEntity="\Objects\UserBundle\Entity\SocialAccounts", mappedBy="user",cascade={"remove", "persist"})
     */
    private $socialAccounts;

    /**
     * @ORM\ManyToMany(targetEntity="\Objects\UserBundle\Entity\Role")
     * @ORM\JoinTable(name="user_role",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)},
     *     inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)}
     * )
     * @var \Doctrine\Common\Collections\ArrayCollection $userRoles
     */
    private $userRoles;

    /**
     * @var string $loginName
     *
     * @ORM\Column(name="loginName", type="string", length=255, nullable=true, unique=true)
     * @Assert\NotBlank(groups={"loginName"})
     * @Assert\Regex(pattern="/^\w+$/u", groups={"loginName"}, message="Only characters, numbers and _")
     */
    private $loginName;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     * @Assert\NotBlank(groups={"signup", "edit", "email"})
     * @Assert\Email(groups={"signup", "edit", "email"})
     */
    private $email;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=255)
     */
    private $password;

    /**
     * @var string $userPassword
     * @Assert\Length(min=6, groups={"signup", "edit", "password"})
     * @Assert\NotBlank(groups={"signup", "password"})
     */
    private $userPassword;

    /**
     * @var string $oldPassword
     * @Assert\NotBlank(groups={"oldPassword"})
     * @SecurityAssert\UserPassword(groups={"oldPassword"})
     */
    private $oldPassword;

    /**
     * @var string $confirmationCode
     *
     * @ORM\Column(name="confirmationCode", type="string", length=32)
     */
    private $confirmationCode;

    /**
     * @var date $createdAt
     *
     * @ORM\Column(name="createdAt", type="date")
     */
    private $createdAt;

    /**
     * @var datetime $lastSeen
     *
     * @ORM\Column(name="lastSeen", type="datetime")
     */
    private $lastSeen;

    /**
     * @var string $firstName
     * @Assert\NotBlank(groups={"firstName", "edit"})
     * @ORM\Column(name="firstName", type="string", length=255)
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
     * @Assert\Url(groups={"edit", "url"})
     */
    private $url;

    /**
     * @var string $countryCode
     *
     * @ORM\Column(name="country_code", type="string", length=2, nullable=true)
     * @Assert\Country(groups={"edit", "country"})
     */
    private $countryCode;

    /**
     * @var string $suggestedLanguage
     *
     * @Assert\Language(groups={"edit", "language"})
     * @ORM\Column(name="suggested_language", type="string", length=2, nullable=true)
     */
    private $suggestedLanguage = 'en';

    /**
     * @var string
     *
     * @ORM\Column(name="googleId", type="string", length=255, nullable=true, unique=true)
     */
    private $googleId;

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
     * @var string $salt
     * @ORM\Column(name="salt", type="string", length=32)
     */
    private $salt;

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
     * @Assert\Image(groups={"image", "edit"})
     * @var \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    private $file;

    /**
     * Set image
     *
     * @param string $image
     * @return User
     */
    public function setImage($image) {
        $this->image = $image;
        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set file
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @return User
     */
    public function setFile($file) {
        $this->file = $file;
        //check if we have an old image
        if ($this->image) {
            //store the old name to delete on the update
            $this->temp = $this->image;
            $this->image = NULL;
        } else {
            $this->image = 'initial';
        }
        return $this;
    }

    /**
     * Get file
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * this function is used to delete the current image
     * the deleting of the current object will also delete the image and you do not need to call this function
     * if you call this function before you remove the object the image will not be removed
     */
    public function removeImage() {
        //check if we have an old image
        if ($this->image) {
            //store the old name to delete on the update
            $this->temp = $this->image;
            //delete the current image
            $this->image = NULL;
        }
    }

    /**
     * create the the directory if not found
     * @param string $directoryPath
     * @throws \Exception if the directory can not be created
     */
    private function createDirectory($directoryPath) {
        if (!@is_dir($directoryPath)) {
            $oldumask = umask(0);
            $success = @mkdir($directoryPath, 0755, TRUE);
            umask($oldumask);
            if (!$success) {
                throw new \Exception("Can not create the directory $directoryPath");
            }
        }
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload() {
        if (NULL !== $this->file && (NULL === $this->image || 'initial' === $this->image)) {
            //get the image extension
            $extension = $this->file->guessExtension();
            //generate a random image name
            $img = uniqid();
            //get the image upload directory
            $uploadDir = $this->getUploadRootDir();
            $this->createDirectory($uploadDir);
            //check that the file name does not exist
            while (@file_exists("$uploadDir/$img.$extension")) {
                //try to find a new unique name
                $img = uniqid();
            }
            //set the image new name
            $this->image = "$img.$extension";
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload() {
        if (NULL !== $this->file) {
            // you must throw an exception here if the file cannot be moved
            // so that the entity is not persisted to the database
            // which the UploadedFile move() method does
            $this->file->move($this->getUploadRootDir(), $this->image);
            //remove the file as you do not need it any more
            $this->file = NULL;
        }
        //check if we have an old image
        if ($this->temp) {
            //try to delete the old image
            @unlink($this->getUploadRootDir() . '/' . $this->temp);
            //clear the temp image
            $this->temp = NULL;
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
    public function getAbsolutePath() {
        return $this->getUploadRootDir() . '/' . $this->image;
    }

    /**
     * @return string the relative path of image starting from web directory
     */
    public function getWebPath() {
        return NULL === $this->image ? NULL : $this->getUploadDir() . '/' . $this->image;
    }

    /**
     * @return string the path of upload directory starting of root
     */
    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    /**
     * @param $width the desired image width
     * @param $height the desired image height
     * @return string the htaccess file url pattern which map to timthumb url
     */
    public function getSmallImageUrl($width = 50, $height = 50) {
        return NULL === $this->image ? NULL : "user-profile-image/$width/$height/$this->image";
    }

    /**
     * @return string the document upload directory path starting from web folder
     */
    private function getUploadDir() {
        return 'uploads/users-profile-images';
    }

    /**
     * download and set the image from url
     * @param string $imageUrl
     * @return boolean true on success and false on failure
     */
    public function setImageFromUrl($imageUrl) {
        $urlParts = explode('.', $imageUrl);
        if (count($urlParts) > 1) {
            $extension = array_pop($urlParts);
            if (strcasecmp($extension, 'jpg') === 0 || strcasecmp($extension, 'jpeg') === 0 || strcasecmp($extension, 'png') === 0 || strcasecmp($extension, 'gif') === 0) {
                $fileContent = @file_get_contents($imageUrl);
                if ($fileContent !== false) {
                    $uploadDir = $this->getUploadRootDir();
                    $this->createDirectory($uploadDir);
                    $img = uniqid();
                    while (@file_exists("$uploadDir/$img.$extension")) {
                        $img = uniqid();
                    }
                    $inserted = @file_put_contents("$uploadDir/$img.$extension", $fileContent);
                    if ($inserted !== false) {
                        if ($this->image) {
                            $this->temp = $this->image;
                        }
                        $this->image = "$img.$extension";
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * initialize the main default attributes
     */
    public function __construct() {
        $this->createdAt = new \DateTime();
        $this->lastSeen = new \DateTime();
        $this->confirmationCode = md5(uniqid(rand()));
        $this->salt = md5(time());
        $this->userRoles = new ArrayCollection();
    }

    /**
     * @return string the object name
     */
    public function __toString() {
        if ($this->lastName) {
            return "$this->firstName $this->lastName";
        }
        return (string) $this->firstName;
    }

    /**
     * this function is used by php to know which attributes to serialize
     * the returned array must not contain any one to one or one to many relation object
     * @return array
     */
    public function __sleep() {
        return array(
            'id', 'loginName', 'email', 'password', 'confirmationCode',
            'createdAt', 'lastSeen', 'firstName', 'lastName', 'about',
            'gender', 'dateOfBirth', 'url', 'countryCode', 'suggestedLanguage',
            'locked', 'enabled', 'salt', 'image'
        );
    }

    /**
     * @return string the site map xml files folder name it has to be the same
     * as the show user profile route prefix
     */
    public function getSiteMapWebFolder() {
        return 'user';
    }

    /**
     * this function will set a valid random password for the user
     */
    public function setRandomPassword() {
        $this->setUserPassword(rand());
    }

    /**
     * set the first name for the user
     * @ORM\PrePersist()
     */
    public function setValidFirstName() {
        if (!$this->firstName) {
            $this->setFirstName($this->getUsername());
        }
    }

    /**
     * this function will set the valid password for the user
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function setValidPassword() {
        //check if we have a password
        if ($this->getUserPassword()) {
            //hash the password
            $this->setPassword($this->hashPassword($this->getUserPassword()));
        } else {
            //check if the object is new
            if ($this->getId() === NULL) {
                //new object set a random password
                $this->setRandomPassword();
                //hash the password
                $this->setPassword($this->hashPassword($this->getUserPassword()));
            }
        }
    }

    /**
     * this function will hash a password and return the hashed value
     * the encoding has to be the same as the one in the project security.yml file
     * @param string $password the password to return it is hash
     */
    private function hashPassword($password) {
        //create an encoder object
        $encoder = new MessageDigestPasswordEncoder('sha512', true, 10);
        //return the hashed password
        return $encoder->encodePassword($password, $this->getSalt());
    }

    /**
     * Set oldPassword
     *
     * @param string $oldPassword
     */
    public function setOldPassword($oldPassword) {
        $this->oldPassword = $oldPassword;
    }

    /**
     * Get oldPassword
     *
     * @return string
     */
    public function getOldPassword() {
        return $this->oldPassword;
    }

    /**
     * Set userPassword
     *
     * @param string $password
     */
    public function setUserPassword($password) {
        $this->userPassword = $password;
    }

    /**
     * @return string the user password
     */
    public function getUserPassword() {
        return $this->userPassword;
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
        //remove the user password
        $this->userPassword = null;
        $this->oldPassword = null;
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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set loginName
     *
     * @param string $loginName
     */
    public function setLoginName($loginName) {
        $this->loginName = $loginName;
    }

    /**
     * Get loginName
     *
     * @return string
     */
    public function getLoginName() {
        return $this->loginName;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * Set confirmationCode
     *
     * @param string $confirmationCode
     */
    public function setConfirmationCode($confirmationCode) {
        $this->confirmationCode = $confirmationCode;
    }

    /**
     * Get confirmationCode
     *
     * @return string
     */
    public function getConfirmationCode() {
        return $this->confirmationCode;
    }

    /**
     * Get createdAt
     *
     * @return date
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     */
    public function setFirstName($firstName) {
        $this->firstName = $firstName;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName() {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     */
    public function setLastName($lastName) {
        $this->lastName = $lastName;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName() {
        return $this->lastName;
    }

    /**
     * Set about
     *
     * @param text $about
     */
    public function setAbout($about) {
        $this->about = $about;
    }

    /**
     * Get about
     *
     * @return text
     */
    public function getAbout() {
        return $this->about;
    }

    /**
     * Set gender
     *
     * @param boolean $gender
     */
    public function setGender($gender) {
        $this->gender = $gender;
    }

    /**
     * Get gender
     *
     * @return boolean
     */
    public function getGender() {
        return $this->gender;
    }

    /**
     * this function will return the string representing the user gender
     * @return string gender type
     */
    public function getGenderString() {
        if ($this->gender === NULL) {
            return 'unknown';
        }
        if ($this->gender === 0) {
            return 'Female';
        }
        if ($this->gender === 1) {
            return 'Male';
        }
    }

    /**
     * this function will return the user country name
     * @param string $locale the language code to display the country name in example: ar, en.
     * @return NULL|string the country name
     */
    public function getCountryName($locale = null) {
        //check if we have a country code
        if ($this->countryCode) {
            if (!$locale) {
                $locale = $this->suggestedLanguage;
            }
            //return the country name
            return \Locale::getDisplayRegion($this->suggestedLanguage . '_' . $this->countryCode, $locale);
        }
        return NULL;
    }

    /**
     * Set dateOfBirth
     *
     * @param date $dateOfBirth
     */
    public function setDateOfBirth($dateOfBirth) {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * Get dateOfBirth
     *
     * @return date
     */
    public function getDateOfBirth() {
        return $this->dateOfBirth;
    }

    /**
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set countryCode
     *
     * @param string $countryCode
     */
    public function setCountryCode($countryCode) {
        $this->countryCode = $countryCode;
    }

    /**
     * Get countryCode
     *
     * @return string
     */
    public function getCountryCode() {
        return $this->countryCode;
    }

    /**
     * Set suggestedLanguage
     *
     * @param string $suggestedLanguage
     */
    public function setSuggestedLanguage($suggestedLanguage) {
        $this->suggestedLanguage = $suggestedLanguage;
    }

    /**
     * Get suggestedLanguage
     *
     * @return string
     */
    public function getSuggestedLanguage() {
        return $this->suggestedLanguage;
    }

    /**
     * Set locked
     *
     * @param boolean $locked
     */
    public function setLocked($locked) {
        $this->locked = $locked;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked() {
        return $this->locked;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled() {
        return $this->enabled;
    }

    /**
     * Set salt
     *
     * @param string $salt
     */
    public function setSalt($salt) {
        $this->salt = $salt;
    }

    /**
     * Add userRoles
     *
     * @param Objects\UserBundle\Entity\Role $userRoles
     */
    public function addRole(\Objects\UserBundle\Entity\Role $userRoles) {
        if (!$this->userRoles->contains($userRoles)) {
            $this->userRoles[] = $userRoles;
        }
    }

    /**
     * Get userRoles
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getUserRoles() {
        return $this->userRoles;
    }

    /**
     * Set socialAccounts
     *
     * @param Objects\UserBundle\Entity\SocialAccounts $socialAccounts
     */
    public function setSocialAccounts(\Objects\UserBundle\Entity\SocialAccounts $socialAccounts) {
        $this->socialAccounts = $socialAccounts;
    }

    /**
     * Get socialAccounts
     *
     * @return Objects\UserBundle\Entity\SocialAccounts
     */
    public function getSocialAccounts() {
        return $this->socialAccounts;
    }

    /**
     * Set lastSeen
     *
     * @param datetime $lastSeen
     */
    public function setLastSeen($lastSeen) {
        $this->lastSeen = $lastSeen;
    }

    /**
     * Get lastSeen
     *
     * @return datetime
     */
    public function getLastSeen() {
        return $this->lastSeen;
    }

    /**
     * Set googleId
     *
     * @param string $googleId
     * @return User
     */
    public function setGoogleId($googleId) {
        $this->googleId = $googleId;
        return $this;
    }

    /**
     * Get googleId
     *
     * @return string
     */
    public function getGoogleId() {
        return $this->googleId;
    }

}