<?php

namespace Objects\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\HttpFoundation\Response;
use Objects\UserBundle\Entity\User;
use Objects\UserBundle\Form\UserSignUp;
use Objects\UserBundle\Form\UserSignUpPopUp;

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
     * this function is used to save the user data in the database and then send him a welcome message
     * and then try to login the user and redirect him to homepage or login page on fail
     * @author Mahmoud
     * @param \Objects\UserBundle\Entity\User $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function finishSignUp($user) {
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
        $body = $this->renderView('ObjectsUserBundle:User:Emails\welcome_to_site.html.twig', array(
            'user' => $user,
            'password' => $user->getPassword(),
            'active' => $active
                ));
        //save the user data in the database
        $em = $this->getDoctrine()->getEntityManager();
        //get a user role object
        $role = $em->getRepository('ObjectsUserBundle:Role')->findOneByName($roleName);
        //set user role
        $user->addRole($role);
        //hash the password before storing in the database
        $user->hashPassword();
        //store the object in the database
        $em->persist($user);
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
                    $body = $this->renderView('ObjectsUserBundle:User:Emails\forgot_your_password.html.twig', array('user' => $user));
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

}
