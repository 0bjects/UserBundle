<?php

namespace Objects\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class UserSignUpPopUp extends AbstractType {

    public function buildForm(FormBuilder $builder, array $options) {
        $builder
                ->add('loginName')
                ->add('email')
                ->add('userPassword')
        ;
    }

    public function getName() {
        return 'objects_userbundle_user_sign_up_pop_up';
    }

    public function getDefaultOptions(array $options) {
        $options['validation_groups'] = array('signup');
        return $options;
    }

}
