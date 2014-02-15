<?php

namespace Objects\UserBundle\Listener;

use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * this class is for listenning on each request the user make
 * if the user last seen time is old this class will update the user last seen time
 * @author Mahmoud
 */
class UpdateUserLastSeenListener {

    private $securityContext;
    private $entityManager;

    public function __construct(SecurityContextInterface $securityContext, $doctrine) {
        $this->securityContext = $securityContext;
        $this->entityManager = $doctrine->getManager();
    }

    public function onRequest() {
        //get the token from the firewall
        $token = $this->securityContext->getToken();
        //check if we have a logged in user
        if ($token && $this->securityContext->isGranted('ROLE_NOTACTIVE')) {
            //get the user objcet
            $user = $token->getUser();
            //get the current time
            $minimumValidTime = new \DateTime();
            //modify the time
            $minimumValidTime->modify('-5 minute');
            //get the user last seen time
            $userLastSeenTime = $user->getLastSeen();
            //check if the user time is valid
            if ($userLastSeenTime < $minimumValidTime) {
                //not valid time update the user last seen time
                $user->setLastSeen(new \DateTime());
                //save in the database
                $this->entityManager->flush();
            }
        }
    }

}
