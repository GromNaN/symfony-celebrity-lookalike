<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Image;

class UploadForm extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'mapped' => false,
                'required' => true,
            ])
            ->add('picture', FileType::class, [
                'label' => 'Picture',
                'mapped' => false,
                'required' => true,
                'constraints' => new Image(maxSize: '12M'),
            ]);
    }
}
