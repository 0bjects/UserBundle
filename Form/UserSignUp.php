<?php

namespace Objects\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class UserSignUp extends AbstractType {

    public function buildForm(FormBuilder $builder, array $options) {
        $builder
                ->add('loginName')
                ->add('email', 'repeated', array(
                    'type' => 'email',
                    'first_name' => 'Email',
                    'second_name' => 'ReEmail',
                    'invalid_message' => "The emails don't match",
                ))
                ->add('password', 'repeated', array(
                    'type' => 'password',
                    'first_name' => 'Password',
                    'second_name' => 'RePassword',
                    'invalid_message' => "The passwords don't match",
                ));
        ;
    }

    public function getName() {
        return 'objects_userbundle_user_sign_up';
    }

    public function getDefaultOptions(array $options) {
        $options['validation_groups'] = array('signup');
        return $options;
    }

}
