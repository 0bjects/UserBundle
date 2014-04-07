<?php

namespace Objects\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\HttpFoundation\Response;
use Objects\APIBundle\Controller\TwitterController;
use Objects\APIBundle\Controller\LinkedinController;
use Objects\APIBundle\Controller\FacebookController;
use Objects\UserBundle\Entity\SocialAccounts;
use Objects\UserBundle\Entity\User;

class UserController extends Controller {

    /**
     * the main login action
     * @author Mahmoud
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction() {
        //initialize an emtpy message string
        $message = '';
        //check if we have a logged in user
        if ($this->has('security.context') && $this->getUser() && TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            //set a hint message for the user
            $message = $this->get('translator')->trans('you will be logged out and logged in as the new user');
        }
        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        $view = 'ObjectsUserBundle:User:login.html.twig';
        if ($request->isXmlHttpRequest()) {
            $view = 'ObjectsUserBundle:User:login_popup.html.twig';
        }
        $container = $this->container;
        $twitterSignupEnabled = $container->getParameter('twitter_signup_enabled');
        $facebookSignupEnabled = $container->getParameter('facebook_signup_enabled');
        $linkedinSignupEnabled = $container->getParameter('linkedin_signup_enabled');
        $googleSignupEnabled = $container->getParameter('google_signup_enabled');
        return $this->render($view, array(
                    'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                    'error' => $error,
                    'message' => $message,
                    'twitterSignupEnabled' => $twitterSignupEnabled,
                    'facebookSignupEnabled' => $facebookSignupEnabled,
                    'linkedinSignupEnabled' => $linkedinSignupEnabled,
                    'googleSignupEnabled' => $googleSignupEnabled,
                    'loginNameRequired' => $container->getParameter('login_name_required')
        ));
    }

    /**
     * this funcion redirects the user to specific url
     * @author Mahmoud
     * @return \Symfony\Component\HttpFoundation\Response a redirect to a url
     */
    public function redirectUserAction() {
        //get the session object
        $session = $this->getRequest()->getSession();
        //check if we have a url to redirect to
        $rediretUrl = $session->get('redirectUrl', FALSE);
        if (!$rediretUrl) {
            //check if firewall redirected the user
            $rediretUrl = $session->get('_security.secured_area.target_path');
            if (!$rediretUrl) {
                //redirect to home page
                $rediretUrl = '/';
                //get the current enviroment
                $enviroment = $this->container->getParameter('kernel.environment');
                //check if this is the development enviroment
                if ($enviroment == 'dev') {
                    //add the development enviroment entry point
                    $rediretUrl .= 'app_dev.php/';
                }
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
        //initialize an emtpy message string
        $message = '';
        //check if we have a logged in user
        if ($this->has('security.context') && $this->getUser() && TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            //set a hint message for the user
            $message = $this->get('translator')->trans('you will be logged out and logged in as the new user');
        }
        //initialize the form validation groups array
        $formValidationGroups = array('signup');
        $container = $this->container;
        //get the login name configuration
        $loginNameRequired = $container->getParameter('login_name_required');
        //check if the login name is required
        if ($loginNameRequired) {
            //add the login name group to the form validation array
            $formValidationGroups [] = 'loginName';
        }
        //get the request object
        $request = $this->getRequest();
        //create an emtpy user object
        $user = new User();
        //this flag is used in the view to correctly render the widgets
        $popupFlag = FALSE;
        //check if this is an ajax request
        if ($request->isXmlHttpRequest()) {
            //create a signup form
            $formBuilder = $this->createFormBuilder($user, array(
                        'validation_groups' => $formValidationGroups
                    ))
                    ->add('email')
                    ->add('firstName', null, array('required' => false))
                    ->add('userPassword');
            //use the popup twig
            $view = 'ObjectsUserBundle:User:signup_popup.html.twig';
            $popupFlag = TRUE;
        } else {
            //create a signup form
            $formBuilder = $this->createFormBuilder($user, array(
                        'validation_groups' => $formValidationGroups
                    ))
                    ->add('email', 'email')
                    ->add('firstName', null, array('required' => false))
                    ->add('userPassword', 'repeated', array(
                'type' => 'password',
                'first_name' => 'Password',
                'second_name' => 'RePassword',
                'invalid_message' => "The passwords don't match",
            ));
            //use the signup page
            $view = 'ObjectsUserBundle:User:signup.html.twig';
        }
        //check if the login name is required
        if ($loginNameRequired) {
            //add the login name field
            $formBuilder->add('loginName');
        }
        //create the form
        $form = $formBuilder->getForm();
        //check if this is the user posted his data
        if ($request->getMethod() == 'POST') {
            //fill the form data from the request
            $form->handleRequest($request);
            //check if the form values are correct
            if ($form->isValid()) {
                //get the user object from the form
                $user = $form->getData();
                if (!$loginNameRequired) {
                    $user->setLoginName($this->suggestLoginName($user->__toString()));
                }
                //user data are valid finish the signup process
                return $this->finishSignUp($user);
            }
        }
        $twitterSignupEnabled = $container->getParameter('twitter_signup_enabled');
        $facebookSignupEnabled = $container->getParameter('facebook_signup_enabled');
        $linkedinSignupEnabled = $container->getParameter('linkedin_signup_enabled');
        $googleSignupEnabled = $container->getParameter('google_signup_enabled');
        return $this->render($view, array(
                    'form' => $form->createView(),
                    'loginNameRequired' => $loginNameRequired,
                    'message' => $message,
                    'popupFlag' => $popupFlag,
                    'twitterSignupEnabled' => $twitterSignupEnabled,
                    'facebookSignupEnabled' => $facebookSignupEnabled,
                    'linkedinSignupEnabled' => $linkedinSignupEnabled,
                    'googleSignupEnabled' => $googleSignupEnabled
        ));
    }

    /**
     * the edit action
     * @author Mahmoud
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction() {
        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
        //get the container object
        $container = $this->container;
        //get the translator object
        $translator = $this->get('translator');
        //get the user object from the firewall
        $loggedInUser = $this->getUser();
        //get the user social accounts object
        $socialAccounts = $loggedInUser->getSocialAccounts();
        //initialize the success message
        $successMessage = FALSE;
        //initialize the redirect flag
        $redirect = FALSE;
        //initialize the form validation groups array
        $formValidationGroups = array('edit');
        //initialize the old password to not required
        $oldPassword = FALSE;
        //initialize the change user name to false
        $changeUserName = FALSE;
        //check if the user is logged in from the login form
        if (FALSE === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
            //mark the old password as required
            $oldPassword = TRUE;
        }
        //check if the user can change the login name
        if ($container->getParameter('login_name_required') && TRUE === $this->get('security.context')->isGranted('ROLE_UPDATABLE_USERNAME')) {
            //make the user able to change his user name
            $changeUserName = TRUE;
        }
        //check if the old password is required
        if ($oldPassword) {
            //add the old password group to the form validation array
            $formValidationGroups [] = 'oldPassword';
        }
        //check if the user can change his user name
        if ($changeUserName) {
            //add the login name group to the form validation array
            $formValidationGroups [] = 'loginName';
        }
        //get the old user email
        $oldEmail = $loggedInUser->getEmail();
        //get the old user name
        $oldLoginName = $loggedInUser->getLoginName();
        //create a password form
        $formBuilder = $this->createFormBuilder($loggedInUser, array(
                    'validation_groups' => $formValidationGroups
                ))
                ->add('userPassword', 'repeated', array(
                    'type' => 'password',
                    'first_name' => "Password",
                    'second_name' => "RePassword",
                    'invalid_message' => "The passwords don't match",
                    'required' => false
                ))
                ->add('gender', 'choice', array(
                    'choices' => array('1' => $translator->trans('Male'), '0' => $translator->trans('Female')),
                    'required' => false,
                    'expanded' => true,
                    'empty_value' => false,
                    'multiple' => false
                ))
                ->add('dateOfBirth', 'date', array('years' => range(1960, date('Y')), 'required' => FALSE))
                ->add('firstName')
                ->add('lastName')
                ->add('about')
                ->add('url', 'url', array('required' => false))
                ->add('countryCode', 'country', array('required' => false))
                ->add('email')
        ;
        //check if the old password is required
        if ($oldPassword) {
            //add the old password field
            $formBuilder->add('oldPassword', 'password');
        }
        //check if the user can change his user name
        if ($changeUserName) {
            //add the login name field
            $formBuilder->add('loginName');
        }
        //create the form
        $form = $formBuilder->getForm();
        //check if this is the user posted his data
        if ($request->getMethod() == 'POST') {
            //fill the form data from the request
            $form->handleRequest($request);
            //check if the form values are correct
            if ($form->isValid()) {
                //get the user object from the form
                $user = $form->getData();
                //check if we need to change the user to not active
                if ($user->getEmail() != $oldEmail && !$container->getParameter('auto_active')) {
                    //remove the role user
                    foreach ($user->getUserRoles() as $key => $roleObject) {
                        //check if this is the wanted role
                        if ($roleObject->getName() == 'ROLE_USER') {
                            //remove the role from the user
                            $user->getUserRoles()->remove($key);
                            //stop the search
                            break;
                        }
                    }
                    //get the not active role object
                    $role = $em->getRepository('ObjectsUserBundle:Role')->findOneByName('ROLE_NOTACTIVE');
                    //check if the user already has the role
                    if (!$user->getUserRoles()->contains($role)) {
                        //add the role to the user
                        $user->addUserRole($role);
                    }
                    //prepare the body of the email
                    $body = $this->renderView('ObjectsUserBundle:User:Emails\activate_email.txt.twig', array('user' => $user));
                    //prepare the message object
                    $message = \Swift_Message::newInstance()
                            ->setSubject($translator->trans('activate your account'))
                            ->setFrom($container->getParameter('mailer_user'))
                            ->setTo($user->getEmail())
                            ->setBody($body)
                    ;
                    //send the activation mail to the user
                    $this->get('mailer')->send($message);
                }
                //check if the user changed his login name
                if ($changeUserName && $oldLoginName != $user->getLoginName()) {
                    //remove the update user name role
                    foreach ($user->getUserRoles() as $key => $roleObject) {
                        //check if this is the wanted role
                        if ($roleObject->getName() == 'ROLE_UPDATABLE_USERNAME') {
                            //remove the role from the user
                            $user->getUserRoles()->remove($key);
                            //stop the search
                            break;
                        }
                    }
                    //redirect the user to remove the login name from the form and refresh his roles
                    $redirect = TRUE;
                }
                //set the password for the user if changed
                $user->setValidPassword();
                //save the data
                $em->flush();
                //check if the user set a valid old password
                if ($oldPassword) {
                    //redirect the user to remove the old password from the form
                    $redirect = TRUE;
                }
                //check if we need to redirect the user
                if ($redirect) {
                    //set the success flash
                    $session->getFlashBag()->set('success', $translator->trans('Done'));
                    //make the user fully authenticated and refresh his roles
                    try {
                        // create the authentication token
                        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                        // give it to the security context
                        $this->get('security.context')->setToken($token);
                    } catch (\Exception $e) {
                        //can not reload the user object log out the user
                        $this->get('security.context')->setToken(null);
                        //invalidate the current user session
                        $this->getRequest()->getSession()->invalidate();
                        //redirect to the login page
                        return $this->redirect($this->generateUrl('login', array(), TRUE));
                    }
                    //redirect the user
                    return $this->redirect($this->generateUrl('user_edit'));
                }
                //set the success message
                $successMessage = $translator->trans('Done');
            }
        }
        $twitterSignupEnabled = $container->getParameter('twitter_signup_enabled');
        $facebookSignupEnabled = $container->getParameter('facebook_signup_enabled');
        $linkedinSignupEnabled = $container->getParameter('linkedin_signup_enabled');
        $googleSignupEnabled = $container->getParameter('google_signup_enabled');
        return $this->render('ObjectsUserBundle:User:edit.html.twig', array(
                    'form' => $form->createView(),
                    'oldPassword' => $oldPassword,
                    'changeUserName' => $changeUserName,
                    'message' => $successMessage,
                    'socialAccounts' => $socialAccounts,
                    'twitterSignupEnabled' => $twitterSignupEnabled,
                    'facebookSignupEnabled' => $facebookSignupEnabled,
                    'linkedinSignupEnabled' => $linkedinSignupEnabled,
                    'googleSignupEnabled' => $googleSignupEnabled
        ));
    }

    /**
     * this function used to resend activation mail to not actice user
     * @author ahmed
     */
    public function reActivationAction() {
        //get the container object
        $container = $this->container;
        //get the translator object
        $translator = $this->get('translator');
        //get the session object
        $session = $this->getRequest()->getSession();
        //check if we have a logged in user or company
        if (FALSE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            $session->getFlashBag()->set('note', $translator->trans('You need to Login first.'));
            return $this->redirect($this->generateUrl('login'));
        }

        //check if the user is already active
        if (TRUE === $this->get('security.context')->isGranted('ROLE_USER')) {
            //set a notice flag
            $session->getFlashBag()->set('notice', $translator->trans('Your acount is active.'));
            return $this->redirect($this->generateUrl('user_edit'));
        }

        //get the logedin user
        $user = $this->getUser();

        //prepare the body of the email
        $body = $this->renderView('ObjectsUserBundle:User:Emails\activate_email.txt.twig', array('user' => $user));
        //prepare the message object
        $message = \Swift_Message::newInstance()
                ->setSubject($translator->trans('activate your account'))
                ->setFrom($container->getParameter('mailer_user'))
                ->setTo($user->getEmail())
                ->setBody($body)
        ;
        //send the activation mail to the user
        $this->get('mailer')->send($message);
        //set the success flag in the session
        $session->getFlashBag()->set('success', $this->get('translator')->trans('check your email for your activation link'));
        //redirect the user to portal
        return $this->redirect($this->generateUrl('user_edit'));
    }

    /**
     * this action will link the user account to his twitter account
     * @author Mahmoud
     */
    public function twitterLinkAction() {
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
        //reload the user object from the database
        $user = $em->getRepository('ObjectsUserBundle:User')->getUserWithSocialAccounts($this->getUser()->getId());
        //get the request object
        $request = $this->getRequest();
        //get the translator object
        $translator = $this->get('translator');
        //get the session object
        $session = $request->getSession();
        //get the user social account object
        $socialAccounts = $user->getSocialAccounts();
        //the link twitter account route should not be visible to an already linked user
        if ($socialAccounts && $socialAccounts->isTwitterLinked()) {
            $session->getFlashBag()->set('error', $translator->trans('Your account is already linked to another account.'));
        }
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
            //check if we have any user linked to this account before
            $twitterAccount = $em->getRepository('ObjectsUserBundle:SocialAccounts')->findOneByTwitterId($twitterId);
            if ($twitterAccount) {
                $session->getFlashBag()->set('error', $translator->trans('The twitter account is already linked to another account.'));
            } else {
                //check if the user does not have a social account object
                if (!$socialAccounts) {
                    //create new social account for the user
                    $socialAccounts = new SocialAccounts();
                    $socialAccounts->setUser($user);
                    $user->setSocialAccounts($socialAccounts);
                    $em->persist($socialAccounts);
                }
                //set the user twitter data
                $socialAccounts->setTwitterId($twitterId);
                $socialAccounts->setOauthToken($oauth_token);
                $socialAccounts->setOauthTokenSecret($oauth_token_secret);
                $socialAccounts->setScreenName($screen_name);
                //save the data for the user
                $em->flush();
                //set the success flag in the session
                $session->getFlashBag()->set('success', $translator->trans('Your account is now linked to twitter'));
            }
        } else {
            //something went wrong clear the session and set a flash to try again
            $session->clear();
            //set the error flag in the session
            $session->getFlashBag()->set('error', $translator->trans('twitter connection error') . ' <a href="' . $this->generateUrl('twitter_authentication', array('redirectRoute' => 'twitter_link'), TRUE) . '">' . $translator->trans('try again') . '</a>');
        }
        //twitter data not found go to the edit page
        return $this->redirect($this->generateUrl('user_edit'));
    }

    /**
     * this function is used to signup or login the user from twitter
     * @author Mahmoud
     */
    public function twitterEnterAction() {
        //get the translator object
        $translator = $this->get('translator');
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
            $em = $this->getDoctrine()->getManager();
            //check if the user twitter id is in our database
            $user = $em->getRepository('ObjectsUserBundle:SocialAccounts')->getUserWithRolesByTwitterId($twitterId);
            //check if we found the user
            if ($user) {
                //get the social accounts object object
                $socialAccounts = $user->getSocialAccounts();
                //user found check if the access tokens have changed
                if ($socialAccounts->getOauthToken() != $oauth_token) {
                    //tokens changed update the tokens
                    $socialAccounts->setOauthToken($oauth_token);
                    $socialAccounts->setOauthTokenSecret($oauth_token_secret);
                    //save the new access tokens
                    $em->flush();
                }
                //try to login the user
                try {
                    // create the authentication token
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    // give it to the security context
                    $this->get('security.context')->setToken($token);
                    //redirect the user
                    return $this->redirectUserAction();
                } catch (\Exception $e) {
                    //set the error flag in the session
                    $session->getFlashBag()->set('error', $translator->trans('twitter connection error') . ' <a href="' . $this->generateUrl('twitter_authentication', array('redirectRoute' => 'twitter_enter')) . '">' . $translator->trans('try again') . '</a>');
                    //can not reload the user object log out the user
                    $this->get('security.context')->setToken(null);
                    //invalidate the current user session
                    $session->invalidate();
                    //redirect to the login page
                    return $this->redirect($this->generateUrl('login'));
                }
            }
            //create a new user object
            $user = new User();
            //create an email form
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
                $form->handleRequest($request);
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
                    //check if we need to set a login name
                    if ($container->getParameter('login_name_required')) {
                        //set a valid login name
                        $user->setLoginName($this->suggestLoginName($screen_name));
                    }
                    //user data are valid finish the signup process
                    return $this->finishSignUp($user);
                }
            }
            return $this->render('ObjectsUserBundle:User:twitter_signup.html.twig', array(
                        'form' => $form->createView()
            ));
        } else {
            //something went wrong clear the session and set a flash to try again
            $session->clear();
            //set the error flag in the session
            $session->getFlashBag()->set('error', $translator->trans('twitter connection error') . ' <a href="' . $this->generateUrl('twitter_authentication', array('redirectRoute' => 'twitter_enter')) . '">' . $translator->trans('try again') . '</a>');
            //twitter data not found go to the login page
            return $this->redirect($this->generateUrl('login', array(), TRUE));
        }
    }

    /**
     * action handle login/linking/signup via facebook
     * this action is called when facebook dialaog redirect to it
     * @author Mirehan
     * @todo enable signup post on the user wall
     */
    public function facebookAction() {
        //check that a logged in user can not access this action
        if (TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            //go to the home page
            return $this->redirect('/');
        }
        $request = $this->getRequest();
        $session = $request->getSession();
        //get page url that the facebook button in
        $returnURL = $session->get('currentURL', FALSE);
        if (!$returnURL) {
            $returnURL = '/';
        }
        //user access Token
        $shortLive_access_token = $session->get('facebook_short_live_access_token', FALSE);
        //facebook User Object
        $faceUser = $session->get('facebook_user', FALSE);
        // something went wrong
        $facebookError = $session->get('facebook_error', FALSE);

        if ($facebookError || !$faceUser || !$shortLive_access_token) {
            return $this->redirect('/');
        }

        //generate long-live facebook access token access token and expiration date
        $longLive_accessToken = FacebookController::getLongLiveFaceboockAccessToken($this->container->getParameter('fb_app_id'), $this->container->getParameter('fb_app_secret'), $shortLive_access_token);

        $em = $this->getDoctrine()->getManager();

        //check if the user facebook id is in our database
        $socialAccounts = $em->getRepository('ObjectsUserBundle:SocialAccounts')->getUserWithRolesByFaceBookId($faceUser->id);

        if ($socialAccounts) {
            //update long-live facebook access token
            $socialAccounts->setAccessToken($longLive_accessToken['access_token']);
            $socialAccounts->setFbTokenExpireDate(new \DateTime(date('Y-m-d', time() + $longLive_accessToken['expires'])));

            $em->flush();
            //get the user object
            $user = $socialAccounts->getUser();
            //try to login the user
            try {
                // create the authentication token
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                // give it to the security context
                $this->get('security.context')->setToken($token);
                //redirect the user
                return $this->redirectUserAction();
            } catch (\Exception $e) {
                //can not reload the user object log out the user
                $this->get('security.context')->setToken(null);
                //invalidate the current user session
                $this->getRequest()->getSession()->invalidate();
                //redirect to the login page
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
                if (empty($socialAccounts)) {
                    $socialAccounts = new SocialAccounts();
                    $socialAccounts->setUser($user);
                }
                $socialAccounts->setFacebookId($faceUser->id);
                $socialAccounts->setAccessToken($longLive_accessToken['access_token']);
                $socialAccounts->setFbTokenExpireDate(new \DateTime(date('Y-m-d', time() + $longLive_accessToken['expires'])));
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

                    $fbLinkeDAndActivatedmessage = $this->get('translator')->trans('Your Facebook account was successfully Linked to your account') . ' ' . $this->get('translator')->trans('your account is now active');
                    //set flash message to tell user that him/her account has been successfully activated
                    $session->getFlashBag()->set('notice', $fbLinkeDAndActivatedmessage);
                } else {
                    $fbLinkeDmessage = $this->get('translator')->trans('Your Facebook account was successfully Linked to your account');
                    //set flash message to tell user that him/her account has been successfully linked
                    $session->getFlashBag()->set('notice', $fbLinkeDmessage);
                }
                $em->persist($user);
                $em->flush();

                //try to login the user
                try {
                    // create the authentication token
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    // give it to the security context
                    $this->get('security.context')->setToken($token);
                    //redirect the user
                    return $this->redirectUserAction();
                } catch (\Exception $e) {
                    //can not reload the user object log out the user
                    $this->get('security.context')->setToken(null);
                    //invalidate the current user session
                    $this->getRequest()->getSession()->invalidate();
                    //redirect to the login page
                    return $this->redirect($this->generateUrl('login', array(), TRUE));
                }
            } else {

                //user sign up
                $user = new User();
                $user->setEmail($faceUser->email);
                //set a valid login name
                $user->setLoginName($this->suggestLoginName($faceUser->name));
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

                //create $socialAccounts object and set facebook account
                $socialAccounts = new SocialAccounts();
                $socialAccounts->setFacebookId($faceUser->id);
                $socialAccounts->setAccessToken($longLive_accessToken['access_token']);
                $socialAccounts->setFbTokenExpireDate(new \DateTime(date('Y-m-d', time() + $longLive_accessToken['expires'])));
                $socialAccounts->setUser($user);
                $user->setSocialAccounts($socialAccounts);
                $translator = $this->get('translator');

                //TODO use
                //send feed to user profile with sign up
                //FacebookController::postOnUserWallAndFeedAction($faceUser->id, $longLive_accessToken['access_token'], $translator->trans('I have new account on this cool site'), $translator->trans('PROJECT_NAME'), $translator->trans('SITE_DESCRIPTION'), 'PROJECT_ORIGINAL_URL', 'SITE_PICTURE');
                //set flash message to tell user that him/her account has been successfully activated
                $session->getFlashBag()->set('notice', $translator->trans('your account is now active'));
                //user data are valid finish the signup process
                return $this->finishSignUp($user, TRUE);
            }
        }
    }

    /**
     * this action will link the user account to the facebook account
     * @author Mirehan & Mahmoud
     */
    public function facebookLinkAction() {
        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        //user access Token
        $shortLive_access_token = $session->get('facebook_short_live_access_token', FALSE);
        //facebook User Object
        $faceUser = $session->get('facebook_user', FALSE);
        // something went wrong
        $facebookError = $session->get('facebook_error', FALSE);
        //check if we have no errors
        if ($facebookError || !$faceUser || !$shortLive_access_token) {
            return $this->redirect('/');
        }

        //generate long-live facebook access token access token and expiration date
        $longLive_accessToken = FacebookController::getLongLiveFaceboockAccessToken($this->container->getParameter('fb_app_id'), $this->container->getParameter('fb_app_secret'), $shortLive_access_token);

        $em = $this->getDoctrine()->getManager();

        $roleRepository = $this->getDoctrine()->getRepository('ObjectsUserBundle:Role');
        $user = $this->getUser();

        $socialAccountsRepo = $this->getDoctrine()->getRepository('ObjectsUserBundle:SocialAccounts');
        $socialAccount = $socialAccountsRepo->findOneByFacebookId($faceUser->id);

        if (!$socialAccount) {
            $socialAccounts = $user->getSocialAccounts();
            if (empty($socialAccounts)) {
                $socialAccounts = new SocialAccounts();
                $socialAccounts->setUser($user);
                $em->persist($socialAccounts);
            }
            $socialAccounts->setFacebookId($faceUser->id);
            $socialAccounts->setAccessToken($longLive_accessToken['access_token']);
            $socialAccounts->setFbTokenExpireDate(new \DateTime(date('Y-m-d', time() + $longLive_accessToken['expires'])));
            $user->setSocialAccounts($socialAccounts);

            //activate user if is not activated
            //get object of notactive Role
            $notActiveRole = $roleRepository->findOneByName('ROLE_NOTACTIVE');
            if ($user->getUserRoles()->contains($notActiveRole) && $user->getEmail() == $faceUser->email) {
                //get a user role object
                $userRole = $roleRepository->findOneByName('ROLE_USER');
                //remove notactive Role from user in exist
                $user->getUserRoles()->removeElement($notActiveRole);

                $user->getUserRoles()->add($userRole);

                $fbLinkeDAndActivatedmessage = $this->get('translator')->trans('Your Facebook account was successfully Linked to your account') . ' ' . $this->get('translator')->trans('your account is now active');
                //set flash message to tell user that him/her account has been successfully activated
                $session->getFlashBag()->set('notice', $fbLinkeDAndActivatedmessage);
            } else {
                $fbLinkeDmessage = $this->get('translator')->trans('Your Facebook account was successfully Linked to your account');
                //set flash message to tell user that him/her account has been successfully linked
                $session->getFlashBag()->set('notice', $fbLinkeDmessage);
            }
            $em->flush();
        } else {
            $fbLinkeDmessage = $this->get('translator')->trans('Facebook linking attempt was unsuccessful.Your Facebook account is already linked to another account.');
            //set flash message to tell user that him/her account has been successfully linked
            $session->getFlashBag()->set('notice', $fbLinkeDmessage);
        }
        return $this->redirect($this->generateUrl('user_edit'));
    }

    /**
     * this action will unlink the user social data data
     * @author Mahmoud
     * @param string $social twitter | facebook
     */
    public function socialUnlinkAction($social) {
        //get the logged in user object
        $user = $this->getUser();
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
        //get the user social account object
        $socialAccounts = $user->getSocialAccounts();
        if ($social == 'facebook') {
            //unlink the facebook account data
            $socialAccounts->unlinkFacebook();
        }
        if ($social == 'twitter') {
            //unlink the facebook account data
            $socialAccounts->unlinkTwitter();
        }
        if ($social == 'linkedin') {
            //unlink the linkedin account data
            $socialAccounts->unlinkLinkedIn();
        }
        //check if we still need the object
        if (!$socialAccounts->isNeeded()) {
            //remove the object
            $em->remove($socialAccounts);
        }
        //save the changes
        $em->flush();
        //set a success flag in the session
        $this->getRequest()->getSession()->getFlashBag()->set('success', $this->get('translator')->trans('Unlinked successfully'));
        //redirect the user to the edit page
        return $this->redirect($this->generateUrl('user_edit'));
    }

    /**
     * this function is used to save the user data in the database and then send him a welcome message
     * and then try to login the user and redirect him to homepage or login page on fail
     * @author Mahmoud
     * @param \Objects\UserBundle\Entity\User $user
     * @param boolean $active if this flag is set the user will be treated as already active
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function finishSignUp($user, $active = FALSE) {
        //check if the user is already active
        if (!$active) {
            //get the activation configurations
            $active = $this->container->getParameter('auto_active');
        }
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
        //add the new user to the entity manager
        $em->persist($user);
        //prepare the body of the email
        $body = $this->renderView('ObjectsUserBundle:User:Emails\welcome_to_site.txt.twig', array(
            'user' => $user,
            'password' => $user->getUserPassword(),
            'active' => $active
        ));
        //check if the user should be active by email or auto activated
        if ($active) {
            //auto active user
            $roleName = 'ROLE_USER';
        } else {
            //user need to activate from email
            $roleName = 'ROLE_NOTACTIVE';
        }
        //get the role repo
        $roleRepository = $em->getRepository('ObjectsUserBundle:Role');
        //get a user role object
        $role = $roleRepository->findOneByName($roleName);
        //get a update userName role object
        $roleUpdateUserName = $roleRepository->findOneByName('ROLE_UPDATABLE_USERNAME');
        //set user roles
        $user->addUserRole($role);
        $user->addUserRole($roleUpdateUserName);
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
        //get the translator object
        $translator = $this->get('translator');
        //initialize a welcome flash message
        $welcomeMessage = $translator->trans('welcome') . ' ' . $user->__toString() . ' ' . $translator->trans('to our site');
        //check if the user need to activate his account
        if (!$active) {
            $welcomeMessage .= ' ' . $this->get('translator')->trans('check your email for your activation link');
        }
        //set the success flag in the session
        $this->getRequest()->getSession()->getFlashBag()->set('success', $welcomeMessage);
        //try to login the user
        try {
            // create the authentication token
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            // give it to the security context
            $this->get('security.context')->setToken($token);
        } catch (\Exception $e) {
            //can not reload the user object log out the user
            $this->get('security.context')->setToken(null);
            //invalidate the current user session
            $this->getRequest()->getSession()->invalidate();
            //redirect to the login page
            return $this->redirect($this->generateUrl('login', array(), TRUE));
        }
        //redirect to home page
        $rediretUrl = '/';
        //get the current enviroment
        $enviroment = $this->container->getParameter('kernel.environment');
        //check if this is the development enviroment
        if ($enviroment == 'dev') {
            //add the development enviroment entry point
            $rediretUrl .= 'app_dev.php/';
        }
        //go to the home page
        return $this->redirect($rediretUrl);
    }

    /**
     * this action will activate the user account and redirect him to the home page
     * after setting either success flag or error flag
     * @author Mahmoud
     * @param string $confirmationCode
     * @param string $email
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function activationAction($confirmationCode, $email) {
        //initial flag to know if the user is logged in
        $loggedIn = FALSE;
        //get the session object
        $session = $this->getRequest()->getSession();
        //get the translator object
        $translator = $this->get('translator');
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
        //check if the user is already active
        if (TRUE === $this->get('security.context')->isGranted('ROLE_USER')) {
            //set a notice flag
            $session->getFlashBag()->set('notice', $translator->trans('Your account is already active.'));
            return $this->redirect($this->generateUrl('user_edit'));
        }
        //check if the user is already active
        if (TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            //get the user object from the firewall
            $user = $this->getUser();
            //set the user to logged in
            $loggedIn = TRUE;
        } else {
            //get the user object from the database
            $user = $em->getRepository('ObjectsUserBundle:User')->findOneBy(array('email' => $email, 'confirmationCode' => $confirmationCode));
        }
        if (!$user || $user->getConfirmationCode() != $confirmationCode || $user->getEmail() != $email) {
            //set an error flag
            $session->getFlashBag()->set('error', $translator->trans('Invalid confirmation code.'));
            //redirect to the login page
            return $this->redirect($this->generateUrl('login'));
        }
        //get a user role object
        $roleUser = $em->getRepository('ObjectsUserBundle:Role')->findOneByName('ROLE_USER');
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
        $user->addUserRole($roleUser);
        //save the new role for the user
        $em->flush();
        //set a success flag
        $session->getFlashBag()->set('success', $translator->trans('Your account is now active.'));
        //check if the user is logged in
        if ($loggedIn) {
            //try to refresh the user object roles in the firewall session
            try {
                // create the authentication token
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                // give it to the security context
                $this->get('security.context')->setToken($token);
            } catch (\Exception $e) {
                //can not reload the user object log out the user
                $this->get('security.context')->setToken(null);
                //invalidate the current user session
                $this->getRequest()->getSession()->invalidate();
                //redirect to the login page
                return $this->redirect($this->generateUrl('login'));
            }
            //go to the edit profile page
            return $this->redirect($this->generateUrl('user_edit'));
        }
        //redirect to the login page
        return $this->redirect($this->generateUrl('login'));
    }

    /**
     * forgot your password action
     * this function gets the user email and sends him email to let him change his password
     * @author mahmoud
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function forgotPasswordAction() {
        //get the request object
        $request = $this->getRequest();
        //create the form
        $form = $this->createFormBuilder()
                ->add('email', 'email', array('constraints' => array(new Email(), new NotBlank())))
                ->getForm();
        //initialze the error string
        $error = FALSE;
        //initialze the success string
        $success = FALSE;
        //check if form is posted
        if ($request->getMethod() == 'POST') {
            //bind the user data to the form
            $form->handleRequest($request);
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
                    $this->getDoctrine()->getManager()->flush();
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
     * @param string $confirmationCode the token sent to the user email
     * @param string $email the user email
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function changePasswordAction($confirmationCode, $email) {
        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        //get the translator object
        $translator = $this->get('translator');
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
        //try to get the user from the database
        $user = $em->getRepository('ObjectsUserBundle:User')->findoneBy(array('email' => $email, 'confirmationCode' => $confirmationCode));
        //check if we found the user
        if (!$user) {
            //set an error flag
            $session->getFlashBag()->set('error', $translator->trans('invalid email or confirmation code'));
            //go to the login page
            return $this->redirect($this->generateUrl('login'));
        }
        //create a password form
        $form = $this->createFormBuilder($user, array(
                    'validation_groups' => array('password')
                ))
                ->add('userPassword', 'repeated', array(
                    'type' => 'password',
                    'first_name' => "Password",
                    'second_name' => "RePassword",
                    'invalid_message' => "The passwords don't match",
                ))
                ->getForm();
        //check if form is posted
        if ($request->getMethod() == 'POST') {
            //bind the user data to the form
            $form->handleRequest($request);
            //check if form is valid
            if ($form->isValid()) {
                //set the password for the user
                $user->setValidPassword();
                //save the new hashed password
                $em->flush();
                //try to login the user
                try {
                    // create the authentication token
                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    // give it to the security context
                    $this->get('security.context')->setToken($token);
                } catch (\Exception $e) {
                    //can not reload the user object log out the user
                    $this->get('security.context')->setToken(null);
                    //invalidate the current user session
                    $session->invalidate();
                    //set the success flag
                    $session->getFlashBag()->set('success', $translator->trans('password changed'));
                    //redirect to the login page
                    return $this->redirect($this->generateUrl('login'));
                }
                //check if the user is active
                if (FALSE === $this->get('security.context')->isGranted('ROLE_USER')) {
                    //activate the user if not active
                    $this->activationAction($confirmationCode, $email);
                    //clear the flashes set by the activation action
                    $session->getFlashBag()->clear();
                }
                //set the success flag
                $session->getFlashBag()->set('success', $translator->trans('password changed'));
                //go to the edit profile page
                return $this->redirect($this->generateUrl('user_edit'));
            }
        }
        return $this->render('ObjectsUserBundle:User:change_password.html.twig', array(
                    'form' => $form->createView(),
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
            //get the session object
            $session = $request->getSession();
            //get the user object from the firewall
            $user = $this->getUser();
            //set the delete flag
            $user->setEnabled(FALSE);
            //get the entity manager
            $em = $this->getDoctrine()->getManager();
            //get the social accounts object
            $socialAccounts = $user->getSocialAccounts();
            //remove the social accounts object if exist
            if ($socialAccounts) {
                $em->remove($socialAccounts);
            }
            //save the changes
            $em->flush();
            //logout the user
            $this->get('security.context')->setToken(null);
            //invalidate the current user session
            $session->invalidate();
            //set the success flag
            $session->getFlashBag()->set('success', $this->get('translator')->trans('Your account has been deleted'));
            //redirect to the login page
            return $this->redirect($this->generateUrl('login'));
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
        $loginName = preg_replace('/[\W_]+/u', '-', strtolower(trim($loginName)));
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
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

    /**
     * this action will link the user account to his linkedin account
     * @author Ahmed
     */
    public function linkedinLinkAction() {
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
        //reload the user object from the database
        $user = $em->getRepository('ObjectsUserBundle:User')->getUserWithSocialAccounts($this->getUser()->getId());
        //get the request object
        $request = $this->getRequest();
        //get the translator object
        $translator = $this->get('translator');
        //get the session object
        $session = $request->getSession();
        //get the oauth token from the session
        $oauth_token = $session->get('oauth_token', FALSE);
        //get the oauth token secret from the session
        $oauth_token_secret = $session->get('oauth_token_secret', FALSE);
        //get linkedIn oauth array from the session
        $linkedIn_oauth = $session->get('oauth_linkedin', FALSE);
        //check if we got linkedin data
        if ($oauth_token && $oauth_token_secret) {
            //get the user data
            $userData = LinkedinController::getUserData($this->container->getParameter('linkedin_api_key'), $this->container->getParameter('linkedin_secret_key'), $linkedIn_oauth);
            //check if we get the user data
            if ($userData) {
                $userData = $userData['linkedin'];
                $userData = json_decode(json_encode((array) simplexml_load_string($userData)), 1);

                //get the user social account object
                $socialAccounts = $user->getSocialAccounts();
                //check if the user does not have a social account object
                if (!$socialAccounts) {
                    //create new social account for the user
                    $socialAccounts = new SocialAccounts();
                    $socialAccounts->setUser($user);
                    $user->setSocialAccounts($socialAccounts);
                    $em->persist($socialAccounts);
                }

                $socialAccounts->setLinkedinOauthToken($oauth_token);
                $socialAccounts->setLinkedinOauthTokenSecret($oauth_token_secret);
                $socialAccounts->setLinkedInId($userData['id']);


                //save the data for the user
                $em->flush();
                //set the success flag in the session
                $session->getFlashBag()->set('success', $translator->trans('Your account is now linked to linkedin'));
            } else {
                //linkedIn data not found go to the edit page
                return $this->redirect($this->generateUrl('user_edit'));
            }
        } else {
            //something went wrong clear the session and set a flash to try again
            $session->clear();
            //set the error flag in the session
            $session->getFlashBag()->set('error', $translator->trans('linkedin connection error') . ' <a href="' . $this->generateUrl('linkedInButton', array('redirectRoute' => 'linkedin_link'), TRUE) . '">' . $translator->trans('try again') . '</a>');
        }
        //linkedIn data not found go to the edit page
        return $this->redirect($this->generateUrl('user_edit'));
    }

    /**
     * this function will receive user data and ask user to enter his email in case new user
     * or will signin the user in case linkedIn user
     * @author Ahmed <a.ibrahim@objects.ws>
     */
    public function linkedInUserDataAction() {
        //check that a logged in user can not access this action
        if (TRUE === $this->get('security.context')->isGranted('ROLE_NOTACTIVE')) {
            //go to the home page
            return $this->redirect('/');
        }

        //get the request object
        $request = $this->getRequest();
        //get the session object
        $session = $request->getSession();
        //get the translator object
        $translator = $this->get('translator');
        //get the oauth token from the session
        $oauth_token = $session->get('oauth_token', FALSE);
        //get the oauth token secret from the session
        $oauth_token_secret = $session->get('oauth_token_secret', FALSE);
        //get linkedIn oauth array from the session
        $linkedIn_oauth = $session->get('oauth_linkedin', FALSE);
        //check if we got linkedin data
        if ($oauth_token && $oauth_token_secret) {
            //get the user data
            $userData = LinkedinController::getUserData($this->container->getParameter('linkedin_api_key'), $this->container->getParameter('linkedin_secret_key'), $linkedIn_oauth);
            //check if we get the user data
            if ($userData) {
                $userData = $userData['linkedin'];
                $userData = json_decode(json_encode((array) simplexml_load_string($userData)), 1);

                //get the entity manager
                $em = $this->getDoctrine()->getManager();
                //check if the user linkedId id is in our database
                $socialAccounts = $em->getRepository('ObjectsUserBundle:SocialAccounts')->findOneBy(array('linkedInId' => $userData['id']));
                //check if we found the user
                if ($socialAccounts) {
                    //user found check if the access tokens have changed
                    if ($socialAccounts->getLinkedinOauthToken() != $oauth_token) {
                        //tokens changed update the tokens
                        $socialAccounts->setLinkedinOauthToken($oauth_token);
                        $socialAccounts->setLinkedinOauthTokenSecret($oauth_token_secret);
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
                        //redirect the user
                        return $this->redirectUserAction();
                    } catch (\Exception $e) {
                        //failed to login the user go to the login page
                        return $this->redirect($this->generateUrl('login', array(), TRUE));
                    }
                }

                /**
                 *
                 * the account of the same email as linkedin account maybe exist but not linked so we will link it
                 * and directly logging the user
                 * if the account is not active we automatically activate it
                 * else will create the account ,sign up the user
                 *
                 * */
                $userRepository = $this->getDoctrine()->getRepository('ObjectsUserBundle:User');
                $roleRepository = $this->getDoctrine()->getRepository('ObjectsUserBundle:Role');
                $user = $userRepository->findOneByEmail($userData['email-address']);
                //if user exist only add linkedin account to social accounts record if user have one
                //if not create new record
                if ($user) {
                    $socialAccounts = $user->getSocialAccounts();
                    if (empty($socialAccounts)) {
                        $socialAccounts = new SocialAccounts();
                        $socialAccounts->setUser($user);
                    }
                    $socialAccounts->setLinkedinOauthToken($oauth_token);
                    $socialAccounts->setLinkedinOauthTokenSecret($oauth_token_secret);
                    $socialAccounts->setLinkedInId($userData['id']);
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

                        $linkedInActivatedmessage = $this->get('translator')->trans('Your LinkedIN account was successfully Linked to your account') . ' ' . $this->get('translator')->trans('your account is now active');
                        //set flash message to tell user that him/her account has been successfully activated
                        $session->getFlashBag()->set('notice', $linkedInActivatedmessage);
                    } else {
                        $linkedInDmessage = $this->get('translator')->trans('Your LinkedIN account was successfully Linked to your account');
                        //set flash message to tell user that him/her account has been successfully linked
                        $session->getFlashBag()->set('notice', $linkedInDmessage);
                    }
                    $em->persist($user);
                    $em->flush();

                    //try to login the user
                    try {
                        // create the authentication token
                        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                        // give it to the security context
                        $this->get('security.context')->setToken($token);
                        //redirect the user
                        return $this->redirectUserAction();
                    } catch (\Exception $e) {
                        //can not reload the user object log out the user
                        $this->get('security.context')->setToken(null);
                        //invalidate the current user session
                        $this->getRequest()->getSession()->invalidate();
                        //redirect to the login page
                        return $this->redirect($this->generateUrl('login', array(), TRUE));
                    }
                }


                //create a new user object
                $user = new User();
                //get the container object
                $container = $this->container;
                $newUserName = '';
                //set the name
                if (isset($userData['first-name'])) {
                    $user->setFirstName($userData['first-name']);
                    $newUserName = $userData['first-name'];
                }
                if (isset($userData['last-name'])) {
                    $user->setLastName($userData['last-name']);
                    $newUserName .= '_' . $userData['last-name'];
                }
                //set a valid login name
                $user->setLoginName($this->suggestLoginName($newUserName));
                //set the profile url
                if (isset($userData['site-standard-profile-request']['url'])) {
                    $user->setUrl($userData['site-standard-profile-request']['url']);
                }
                //set the about text
                if (isset($userData['summary'])) {
                    $user->setAbout($userData['summary']);
                }
                //set user country code
                if (isset($userData['location']['country']['code'])) {
                    $user->setCountryCode($userData['location']['country']['code']);
                }
                //try to download the user image from linkedIn if user has one
                if (isset($userData['picture-url'])) {
                    $image = LinkedinController::downloadLinkedInImage($userData['picture-url'], $user->getUploadRootDir());
                    //check if we got an image
                    if ($image) {
                        //add the image to the user
                        $user->setImage($image);
                    }
                }
                //set the user email
                if (isset($userData['email-address'])) {
                    $user->setEmail($userData['email-address']);
                }
                //set the user dateOfBirth
                if (isset($userData['date-of-birth'])) {
                    $user->setDateOfBirth(new \DateTime($userData['date-of-birth']['year'] . '-' . $userData['date-of-birth']['month'] . '-' . $userData['date-of-birth']['day']));
                }

                //create social accounts object
                $socialAccounts = new SocialAccounts();
                $socialAccounts->setLinkedinOauthToken($oauth_token);
                $socialAccounts->setLinkedinOauthTokenSecret($oauth_token_secret);
                $socialAccounts->setLinkedInId($userData['id']);
                $socialAccounts->setUser($user);
                //set the user linkedIn info
                $user->setSocialAccounts($socialAccounts);

                //user data are valid finish the signup process
                return $this->finishSignUp($user);
            } else {
                //linkedIn data not found go to the login page
                return $this->redirect($this->generateUrl('login', array(), TRUE));
            }
        } else {
            //linkedIn data not found go to the login page
            return $this->redirect($this->generateUrl('login', array(), TRUE));
        }
    }

    /**
     * this function will used to check if loginName is exist
     * @author Ahmed
     * @param string $loginName
     */
    public function loginNameCheckAction($loginName) {
        //get the entity manager
        $em = $this->getDoctrine()->getManager();
        //reload the user object from the database
        $user = $em->getRepository('ObjectsUserBundle:User');

        $userObject = $user->findOneBy(array('loginName' => $loginName));
        if ($userObject) {
            return new Response('exist');
        } else {
            return new Response('not-exist');
        }
    }

    /**
     * signup or login using google oauth
     * @author Mahmoud
     */
    public function googleEnterAction() {
        $session = $this->get('session');
        $googleUser = $session->get('googleUserInfo');
        $googleId = $googleUser['id'];
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('ObjectsUserBundle:User')->getUserWithRolesByGoogleId($googleId);
        if ($user) {
            try {
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $this->get('security.context')->setToken($token);
                return $this->redirectUserAction();
            } catch (\Exception $e) {
                $this->get('security.context')->setToken(null);
                $session->invalidate();
                return $this->redirect($this->generateUrl('login'));
            }
        }
        $container = $this->container;
        $user = new User();
        $user->setEmail($googleUser['email']);
        $user->setGoogleId($googleId);
        $name = '';
        if (isset($googleUser['name'])) {
            $name = $googleUser['name'];
        } elseif (isset($googleUser['given_name'])) {
            $name = $googleUser['given_name'];
        } else {
            $name = 'user';
        }
        $nameParts = explode(' ', $name);
        if (!empty($nameParts[0])) {
            $user->setFirstName($nameParts[0]);
        }
        if (!empty($nameParts[1])) {
            $user->setLastName($nameParts[1]);
        }
        if ($container->getParameter('login_name_required')) {
            $user->setLoginName($this->suggestLoginName($name));
        }
        if (isset($googleUser['link'])) {
            $user->setUrl($googleUser['link']);
        }
        if (isset($googleUser['gender'])) {
            if ($googleUser['gender'] === 'male') {
                $user->setGender(1);
            } elseif ($googleUser['gender'] === 'female') {
                $user->setGender(0);
            }
        }
        if (isset($googleUser['locale'])) {
            $user->setSuggestedLanguage($googleUser['locale']);
        }
        if (isset($googleUser['birthday'])) {
            $user->setDateOfBirth(new \DateTime($googleUser['birthday']));
        }
        if (isset($googleUser['picture'])) {
            $user->setImageFromUrl($googleUser['picture']);
        }
        return $this->finishSignUp($user, true);
    }

}
