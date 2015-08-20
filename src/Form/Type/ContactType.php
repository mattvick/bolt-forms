<?php

namespace Bolt\Extension\Mahango\Forms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Core API functions for MahangoForms
 *
 * Copyright (C) 2014
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Gawain Lynch <gawain.lynch@gmail.com>
 * @author    Matthew Vickery <vickery.matthew@gmail.com>
 * @copyright Copyright (c) 2014
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License 3.0
 */
class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ->setAction($this->generateUrl('target_route'))
            ->setMethod('POST')
            ->add('name', 'text', array(
               'constraints' => array(
                   new Assert\NotBlank(),
                   new Assert\Length(array('min' => 3)),
               ),
           ))
            ->add('email', 'email', array(
               'constraints' => array(
                   new Assert\NotBlank(),
                   new Assert\Email(array('checkMX' => true)),
               ),
           ))
            ->add('gender', 'choice', array(
                'choices' => array(
                    'm' => 'Male', 
                    'f' => 'Female'
                ),
                'expanded' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Choice(array(
                        'choices' => array(
                            'm', 
                            'f',
                        )
                    ))
                )
            ))
            ->add('save', 'submit');
    }

    public function getName()
    {
        return 'contact';
    }
}