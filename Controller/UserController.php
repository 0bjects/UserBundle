<?php

namespace Objects\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Objects\APIBundle\Controller\TwitterController;
use Objects\UserBundle\Entity\SocialAccounts;
use Objects\UserBundle\Entity\User;
use Objects\UserBundle\Form\UserSignUp;
use Objects\UserBundle\Form\UserSignUpPopUp;
use Objects\APIBundle\Controller\FacebookController;
class UserController extends Controller {

    /**
     * the main login action
     * @author Mahmoud
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction() {
        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        //create a new response for the user
        $response = new Response();
        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
        }
        //check if we have an error
        if (!$error) {
            //set the caching to every one
            $response->setPublic();
            //the caching will be different for each encoding
            $response->setVary(array('Accept-Encoding', 'X-Requested-With'));
            //set the response ETag
            $response->setETag('login');
            //set the time before we need to get this page again
            $response->setSharedMaxAge(604800);
            // Check that the Response is not modified for the given Request
            if ($response->isNotModified($request)) {
                // return the 304 Response immediately
                return $response;
            }
        }
        //check if it is an ajax request
        if ($request->isXmlHttpRequest()) {
            //return a pop up render
            return $this->render('ObjectsUserBundle:User:login_popup.html.twig', array(
                        // last username entered by the user
                        'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                        'error' => $error,
                            ), $response);
        }
        //return the main page
        return $this->render('ObjectsUserBundle:User:login.html.twig', array(
                    // last username entered by the user
                    'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                    'error' => $error,
                        ), $response);
    }

    /**
     * this funcion sets the user login time to the current time
     * and redirect the user to previous requested page or home page
     * @author Mahmoud
     * @return \Symfony\Component\HttpFoundation\Response a redirect to the site home page
     */
    public function updateLoginTimeAction() {
        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        //get the user object
        $user = $this->get('security.context')->getToken()->getUser();
        //update the login time
        $user->setLastLoginDateTime(new \DateTime());
        //save the new login time in the database
        $this->getDoctrine()->getEntityManager()->flush();
        //check if we have a url to redirect to
        $rediretUrl = $session->get('redirectUrl', FALSE);
        if (!$rediretUrl) {
            //check if firewall redirected the user
            $rediretUrl = $session->get('_security.target_path');
            if (!$rediretUrl) {
                //redirect to home page
                $rediretUrl = '/';
            }
        } else {
            //remove the redirect url from the session
            $session->remove('redirectUrl');
        }
        return $this->redirect($rediretUrl);
    }

    /**
     * the signup action
     * the link to this page should not be visible for the logged in user
     * @author Mahmoud
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function signUpAction() {
        //check that a logged in user can not access this action
        if (TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            //go to the home page
            return $this->redirect('/');
        }
        //get the request object
        $request = $this->getRequest();
        //create an emtpy user object
        $user = new User();
        //clear the default random password
        $user->setPassword('');
        //check if this is an ajax request
        if ($request->isXmlHttpRequest()) {
            //create a popup form
            $form = $this->createForm(new UserSignUpPopUp(), $user);
            //use the popup twig
            $view = 'ObjectsUserBundle:User:signup_popup.html.twig';
        } else {
            //create a signup form
            $form = $this->createForm(new UserSignUp(), $user);
            //use the signup page
            $view = 'ObjectsUserBundle:User:signup.html.twig';
        }
        //check if this is the user posted his data
        if ($request->getMethod() == 'POST') {
            //fill the form data from the request
            $form->bindRequest($request);
            //check if the form values are correct
            if ($form->isValid()) {
                //get the user object from the form
                $user = $form->getData();
                //user data are valid finish the signup process
                return $this->finishSignUp($user);
            }
        }
        return $this->render($view, array(
                    'form' => $form->createView()
                ));
    }

    /**
     * this function is used to signup or login the user from twitter
     * @author Mahmoud 
     */
    public function twitterEnterAction() {
        //check that a logged in user can not access this action
        if (TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            //go to the home page
            return $this->redirect('/');
        }
        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        //get the oauth token from the session
        $oauth_token = $session->get('oauth_token', FALSE);
        //get the oauth token secret from the session
        $oauth_token_secret = $session->get('oauth_token_secret', FALSE);
        //get the twtiter id from the session
        $twitterId = $session->get('twitterId', FALSE);
        //get the screen name from the session
        $screen_name = $session->get('screen_name', FALSE);
        //check if we got twitter data
        if ($oauth_token && $oauth_token_secret && $twitterId && $screen_name) {
            //get the entity manager
            $em = $this->getDoctrine()->getEntityManager();
            //check if the user twitter id is in our database
            $socialAccounts = $em->getRepository('ObjectsUserBundle:SocialAccounts')->findOneBy(array('twitterId' => $twitterId));
            //check if we found the user
            if ($socialAccounts) {
                //user found check if the access tokens have changed
                if ($socialAccounts->getOauthToken() != $oauth_token) {
                    //tokens changed update the tokens
                    $socialAccounts->setOauthToken($oauth_token);
                    $socialAccounts->setOauthTokenSecret($oauth_token_secret);
                    //save the new access tokens
                    $em->flush();
                }
                //get the user object
                $user = $socialAccounts->getUser();
                //try to login the user
                try {
                    // create the authentication token
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    // give it to the security context
                    $this->container->get('security.context')->setToken($token);
                    //update the login time
                    return $this->updateLoginTimeAction();
                } catch (\Exception $e) {
                    //failed to login the user go to the login page
                    return $this->redirect($this->generateUrl('login', array(), TRUE));
                }
            }
            //create a new user object
            $user = new User();
            //create a password form
            $form = $this->createFormBuilder($user, array(
                        'validation_groups' => array('email')
                    ))
                    ->add('email', 'repeated', array(
                        'type' => 'email',
                        'first_name' => 'Email',
                        'second_name' => 'ReEmail',
                        'invalid_message' => "The emails don't match",
                    ))
                    ->getForm();
            //check if this is the user posted his data
            if ($request->getMethod() == 'POST') {
                //fill the form data from the request
                $form->bindRequest($request);
                //check if the form values are correct
                if ($form->isValid()) {
                    //get the container object
                    $container = $this->container;
                    //get the user object from the form
                    $user = $form->getData();
                    //request additional user data from twitter
                    $content = TwitterController::getCredentials($container->getParameter('consumer_key'), $container->getParameter('consumer_secret'), $oauth_token, $oauth_token_secret);
                    //check if we got the user data
                    if ($content) {
                        //get the name parts
                        $name = explode(' ', $content->name);
                        if (!empty($name[0])) {
                            $user->setFirstName($name[0]);
                        }
                        if (!empty($name[1])) {
                            $user->setLastName($name[1]);
                        }
                        //set the additional data
                        $user->setUrl($content->url);
                        //set the about text
                        $user->setAbout($content->description);
                        //try to download the user image from twitter
                        $image = TwitterController::downloadTwitterImage($content->profile_image_url, $user->getUploadRootDir());
                        //check if we got an image
                        if ($image) {
                            //add the image to the user
                            $user->setImage($image);
                        }
                    }
                    //create social accounts object
                    $socialAccounts = new SocialAccounts();
                    $socialAccounts->setOauthToken($oauth_token);
                    $socialAccounts->setOauthTokenSecret($oauth_token_secret);
                    $socialAccounts->setTwitterId($twitterId);
                    $socialAccounts->setScreenName($screen_name);
                    $socialAccounts->setUser($user);
                    //set the user twitter info
                    $user->setSocialAccounts($socialAccounts);
                    //set a valid login name
                    $user->setLoginName($this->suggestLoginName($screen_name));
                    //user data are valid finish the signup process
                    return $this->finishSignUp($user);
                }
            }
            return $this->render('ObjectsUserBundle:User:twitter_signup.html.twig', array(
                        'form' => $form->createView()
                    ));
        } else {
            //twitter data not found go to the signup page
            return $this->redirect($this->generateUrl('signup', array(), TRUE));
        }
    }

    /**
     * action handle login/linking/signup via facebook
     * this action is called when facebook dialaog redirect to it
     * @author Mirehan
     *
     */
    public function facebookAction(Request $request) {
        //check that a logged in user can not access this action
        if (TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            //go to the home page
            return $this->redirect('/');
        }
        
        $session = $request->getSession();
        //get page url that the facebook button in
        $returnURL = $session->get('currentURL',FALSE);
        if(!$returnURL){
            $returnURL  = '/';
        }
        //user access Token
        $shortLive_access_token = $session->get('facebook_short_live_access_token',FALSE);
        //facebook User Object
        $faceUser = $session->get('facebook_user',FALSE);
        // something went wrong
        $facebookError = $session->get('facebook_error',FALSE);

        if ($facebookError || !$faceUser || !$shortLive_access_token) {
            return $this->redirect('/');
        }

        //generate long-live facebook access token access token and expiration date
        $longLive_accessToken = FacebookController::getLongLiveFaceboockAccessToken($this->container->getParameter('fb_app_id'),$this->container->getParameter('fb_app_secret'),$shortLive_access_token);
        
        $em = $this->getDoctrine()->getEntityManager();
        
        //check if the user facebook id is in our database
        $socialAccounts = $em->getRepository('ObjectsUserBundle:SocialAccounts')->findOneBy(array('facebookId' => $faceUser->id));
        
        

        if ($socialAccounts) {
            //update long-live facebook access token
            $socialAccounts->setAccessToken($longLive_accessToken['access_token']);
            $socialAccounts->setFbTokenExpireDate(new \DateTime(date('Y-m-d', time()+$longLive_accessToken['expires'])));
           
            $em->flush();
            //get the user object
            $user = $socialAccounts->getUser();
            //try to login the user
            try {
                // create the authentication token
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                // give it to the security context
                $this->container->get('security.context')->setToken($token);
                //update the login time
                return $this->updateLoginTimeAction();
            } catch (\Exception $e) {
                //failed to login the user go to the login page
                return $this->redirect($this->generateUrl('login', array(), TRUE));
            }
            
        } else {
            /**
             *
             * the account of the same email as facebook account maybe exist but not linked so we will link it 
             * and directly logging the user
             * if the account is not active we automatically activate it
             * else will create the account ,sign up the user
             * 
             * */
            $userRepository = $this->getDoctrine()->getRepository('ObjectsUserBundle:User');
            $roleRepository = $this->getDoctrine()->getRepository('ObjectsUserBundle:Role');
            $user = $userRepository->findOneByEmail($faceUser->email);
            //if user exist only add facebook account to social accounts record if user have one
            //if not create new record
            if ($user) {
                $socialAccounts = $user->getSocialAccounts();
                if(empty($socialAccounts)){
                    $socialAccounts = new SocialAccounts();
                    $socialAccounts->setUser($user);
                }
                $socialAccounts->setFacebookId($faceUser->id);
                $socialAccounts->setAccessToken($longLive_accessToken['access_token']);
                $socialAccounts->setFbTokenExpireDate(new \DateTime(date('Y-m-d', time()+$longLive_accessToken['expires'])));                
                $user->setSocialAccounts($socialAccounts);
                
                //activate user if is not activated
                //get object of notactive Role
                $notActiveRole = $roleRepository->findOneByName('ROLE_NOTACTIVE');
                if ($user->getUserRoles()->contains($notActiveRole)) {
                    //get a user role object
                    $userRole = $roleRepository->findOneByName('ROLE_USER');
                    //remove notactive Role from user in exist
                    $user->getUserRoles()->removeElement($notActiveRole);

                    $user->getUserRoles()->add($userRole);
                    
                    $fbLinkeDAndActivatedmessage = $this->get('translator')->trans('Your Facebook account was successfully Linked to your account!Your account was successfully activated!');
                    //set flash message to tell user that him/her account has been successfully activated
                    $session->setFlash('notice', $fbLinkeDAndActivatedmessage);
                    
                } else {
                    $fbLinkeDmessage = $this->get('translator')->trans('Your Facebook account was successfully Linked to your account!');
                    //set flash message to tell user that him/her account has been successfully linked
                    $session->setFlash('notice', $fbLinkeDmessage);
                    
                }
                $em->persist($user);
                $em->flush();

                //try to login the user
                try {
                    // create the authentication token
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    // give it to the security context
                    $this->container->get('security.context')->setToken($token);
                    //update the login time
                    return $this->updateLoginTimeAction();
                } catch (\Exception $e) {
                    //failed to login the user go to the login page
                    return $this->redirect($this->generateUrl('login', array(), TRUE));
                }
            } else {
                
                //user sign up
                $user = new User();
                $user->setEmail($faceUser->email);
                //set a valid login name
                $user->setLoginName($this->suggestLoginName(strtolower($faceUser->name)));
                $user->setFirstName($faceUser->first_name);
                $user->setLastName($faceUser->last_name);
                if ($faceUser->gender == 'female') {
                    $user->setGender(0);
                } else {
                    $user->setGender(1);
                }
                //try to download the user image from facebook
                $image = FacebookController::downloadAccountImage($faceUser->id, $user->getUploadRootDir());
                //check if we got an image
                if ($image) {
                    //add the image to the user
                    $user->setImage($image);
                }

                //get a update userName role object
                $role = $roleRepository->findOneByName('ROLE_UPDATABLE_USERNAME');
                //set update role
                $user->getUserRoles()->add($role);
                //create $socialAccounts object and set facebook account
                $socialAccounts = new SocialAccounts();
                $socialAccounts->setFacebookId($faceUser->id);
                $socialAccounts->setAccessToken($longLive_accessToken['access_token']);
                $socialAccounts->setFbTokenExpireDate(new \DateTime(date('Y-m-d', time()+$longLive_accessToken['expires'])));                
                $socialAccounts->setUser($user);
                $user->setSocialAccounts($socialAccounts);
                $translator = $this->get('translator');
                //send feed to user profile with sign up
                $message = $translator->trans('I have new account on this cool site');
                FacebookController::postOnUserWallAndFeedAction($faceUser->id,$longLive_accessToken['access_token'], $message,$translator->trans('PROJECT_NAME'),$translator->trans('SITE_DESCRIPTION'),'PROJECT_ORIGINAL_URL','SITE_PICTURE');
                
                //set flash message to tell user that him/her account has been successfully activated
                $session->setFlash('notice', $translator->trans('Your account was successfully activated!'));
                //user data are valid finish the signup process
                return $this->finishSignUp($user,TRUE);
            }
        }
    }
    /**
     * this function is used to save the user data in the database and then send him a welcome message
     * and then try to login the user and redirect him to homepage or login page on fail
     * @author Mahmoud
     * @param \Objects\UserBundle\Entity\User $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function finishSignUp($user , $active = FALSE) {
        if(!$active)
            //get the activation configurations
            $active = $this->container->getParameter('auto_active');
        
        //check if the user should be active by email or auto activated
        if ($active) {
            //auto active user
            $roleName = 'ROLE_USER';
        } else {
            //user need to activate from email
            $roleName = 'ROLE_NOTACTIVE';
        }
        //prepare the body of the email
        $body = $this->renderView('ObjectsUserBundle:User:Emails\welcome_to_site.txt.twig', array(
            'user' => $user,
            'password' => $user->getPassword(),
            'active' => $active
                ));
        //get the entity manager
        $em = $this->getDoctrine()->getEntityManager();
        //get a user role object
        $role = $em->getRepository('ObjectsUserBundle:Role')->findOneByName($roleName);
        //set user role
        $user->addRole($role);
        //hash the password before storing in the database
        $user->hashPassword();
        //add the new user to the entity manager
        $em->persist($user);
        //store the object in the database
        $em->flush();
        //prepare the message object
        $message = \Swift_Message::newInstance()
                ->setSubject($this->get('translator')->trans('welcome'))
                ->setFrom($this->container->getParameter('mailer_user'))
                ->setTo($user->getEmail())
                ->setBody($body)
        ;
        //send the email
        $this->get('mailer')->send($message);
        //try to login the user
        try {
            // create the authentication token
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            // give it to the security context
            $this->container->get('security.context')->setToken($token);
        } catch (\Exception $e) {
            //failed to login the user go to the login page
            return $this->redirect($this->generateUrl('login', array(), TRUE));
        }
        //go to the home page
        return $this->redirect('/');
    }

    /**
     * this action will activate the user account and redirect him to the home page
     * after setting either success flag or error flag
     * @author Mahmoud
     * @param string $confirmationCode
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function activationAction($confirmationCode) {
        //get the user object from the firewall
        $user = $this->get('security.context')->getToken()->getUser();
        //get the session object
        $session = $this->getRequest()->getSession();
        //get the translator object
        $translator = $this->get('translator');
        //get the entity manager
        $em = $this->getDoctrine()->getEntityManager();
        //get a user role object
        $roleUser = $em->getRepository('ObjectsUserBundle:Role')->findOneByName('ROLE_USER');
        //check if the user is already active (the user might visit the link twice)
        if ($user->getUserRoles()->contains($roleUser)) {
            //set a notice flag
            $session->setFlash('notice', $translator->trans('nothing to do'));
        } else {
            //check if the confirmation code is correct
            if ($user->getConfirmationCode() == $confirmationCode) {
                //get the current user roles
                $userRoles = $user->getUserRoles();
                //try to get the not active role
                foreach ($userRoles as $key => $userRole) {
                    //check if this role is the not active role
                    if ($userRole->getName() == 'ROLE_NOTACTIVE') {
                        //remove the not active role
                        $userRoles->remove($key);
                        //end the search
                        break;
                    }
                }
                //add the user role
                $user->addRole($roleUser);
                //save the new role for the user
                $em->flush();
                //set a success flag
                $session->setFlash('success', $translator->trans('your account is now active'));
            } else {
                //set an error flag
                $session->setFlash('error', $translator->trans('invalid confirmation code'));
            }
        }
        //go to the home page
        return $this->redirect('/');
    }

    /**
     * forgot your password action
     * this function gets the user email and sends him email to let him change his password
     * @author mahmoud
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function forgotPasswordAction() {
        //check that a logged in user can not access this action
        if (TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            return $this->redirect('/');
        }
        //get the request object
        $request = $this->getRequest();
        //prepare the form validation constrains
        $collectionConstraint = new Collection(array(
                    'email' => new Email()
                ));
        //create the form
        $form = $this->createFormBuilder(null, array(
                    'validation_constraint' => $collectionConstraint,
                ))->add('email', 'email')
                ->getForm();
        //initialze the error string
        $error = FALSE;
        //initialze the success string
        $success = FALSE;
        //check if form is posted
        if ($request->getMethod() == 'POST') {
            //bind the user data to the form
            $form->bindRequest($request);
            //check if form is valid
            if ($form->isValid()) {
                //get the translator object
                $translator = $this->get('translator');
                //get the form data
                $data = $form->getData();
                //get the email
                $email = $data['email'];
                //search for the user with the entered email
                $user = $this->getDoctrine()->getRepository('ObjectsUserBundle:User')->findOneBy(array('email' => $email));
                //check if we found the user
                if ($user) {
                    //set a new token for the user
                    $user->setConfirmationCode(md5(uniqid(rand())));
                    //save the new user token into database
                    $this->getDoctrine()->getEntityManager()->flush();
                    //prepare the body of the email
                    $body = $this->renderView('ObjectsUserBundle:User:Emails\forgot_your_password.txt.twig', array('user' => $user));
                    //prepare the message object
                    $message = \Swift_Message::newInstance()
                            ->setSubject($this->get('translator')->trans('forgot your password'))
                            ->setFrom($this->container->getParameter('mailer_user'))
                            ->setTo($user->getEmail())
                            ->setBody($body)
                    ;
                    //send the email
                    $this->get('mailer')->send($message);
                    //set the success message
                    $success = $translator->trans('done please check your email');
                } else {
                    //set the error message
                    $error = $translator->trans('the entered email was not found');
                }
            }
        }
        return $this->render('ObjectsUserBundle:User:forgot_password.html.twig', array(
                    'form' => $form->createView(),
                    'error' => $error,
                    'success' => $success
                ));
    }

    /**
     * the change of password page
     * @author mahmoud
     * @param string|NULL $confirmationCode the token sent to the user email
     * @param string|NULL $email the user email
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function changePasswordAction($confirmationCode = NULL, $email = NULL) {
        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        //get the translator object
        $translator = $this->get('translator');
        //get the entity manager
        $em = $this->getDoctrine()->getEntityManager();
        //the success of login flag used to generate corrcet submit route for the form
        $loginSuccess = FALSE;
        //check if the user came from the email link
        if ($confirmationCode && $email) {
            //try to get the user from the database
            $user = $this->getDoctrine()->getRepository('ObjectsUserBundle:User')->findoneBy(array('email' => $email, 'confirmationCode' => $confirmationCode));
            //check if we found the user
            if ($user) {
                //try to login the user
                try {
                    // create the authentication token
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    // give it to the security context
                    $this->container->get('security.context')->setToken($token);
                    //update the login time
                    $user->setLastLoginDateTime(new \DateTime());
                    //save the new login time
                    $em->flush();
                    //check if the user is active
                    if (FALSE === $this->get('security.context')->isGranted('ROLE_USER')) {
                        //activate the user if not active
                        $this->activationAction($confirmationCode);
                        //clear the flashes set by the activation action
                        $session->clearFlashes();
                    }
                    //set the login success flag
                    $loginSuccess = TRUE;
                } catch (\Exception $e) {
                    
                }
            } else {
                //set an error flag
                $session->setFlash('error', $translator->trans('invalid email or confirmation code'));
                //go to home page
                return $this->redirect('/');
            }
        } else {
            //check if the user is logged in from the login form
            if (FALSE === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
                //set the redirect url to the login action
                $session->set('redirectUrl', $this->generateUrl('change_password', array(), TRUE));
                //require the login from the user
                return $this->redirect($this->generateUrl('login', array(), TRUE));
            } else {
                //get the user object from the firewall
                $user = $this->get('security.context')->getToken()->getUser();
                //set the login success flag
                $loginSuccess = TRUE;
            }
        }
        //create a password form
        $form = $this->createFormBuilder($user, array(
                    'validation_groups' => array('password')
                ))
                ->add('password', 'repeated', array(
                    'type' => 'password',
                    'first_name' => "Password",
                    'second_name' => "RePassword",
                    'invalid_message' => "The passwords don't match",
                ))
                ->getForm();
        //check if form is posted
        if ($request->getMethod() == 'POST') {
            //bind the user data to the form
            $form->bindRequest($request);
            //check if form is valid
            if ($form->isValid()) {
                //encrypt the password
                $user->hashPassword();
                //save the new hashed password
                $em->flush();
                //set the success flag
                $session->setFlash('success', $translator->trans('password changed'));
                //go to home page
                return $this->redirect('/');
            }
        }
        return $this->render('ObjectsUserBundle:User:change_password.html.twig', array(
                    'form' => $form->createView(),
                    'loginSuccess' => $loginSuccess,
                    'user' => $user
                ));
    }

    /**
     * this action will give the user the ability to delete his account
     * it will not actually delete the account it will simply disable it
     * @author Mahmoud
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAccountAction() {
        //get the request object
        $request = $this->getRequest();
        //check if form is posted
        if ($request->getMethod() == 'POST') {
            //get the user object from the firewall
            $user = $this->get('security.context')->getToken()->getUser();
            //set the delete flag
            $user->setEnabled(FALSE);
            //save the delete flag
            $this->getDoctrine()->getEntityManager()->flush();
            //go to home page
            return $this->redirect($this->generateUrl('logout', array(), TRUE));
        }
        return $this->render('ObjectsUserBundle:User:delete_account.html.twig');
    }

    /**
     * this function will check the login name againest the database if the name
     * does not exist the function will return the name otherwise it will try to return
     * a valid login Name
     * @author Alshimaa edited by Mahmoud
     * @param string $loginName
     * @return string a valid login name to use
     */
    private function suggestLoginName($loginName) {
        //get the entity manager
        $em = $this->getDoctrine()->getEntityManager();
        //get the user repo
        $userRepository = $em->getRepository('ObjectsUserBundle:User');
        //try to check if the given name does not exist
        $user = $userRepository->findOneByLoginName($loginName);
        if (!$user) {
            //valid login name
            return $loginName;
        }
        //get a valid one from the database
        return $userRepository->getValidLoginName($loginName);
    }

}
