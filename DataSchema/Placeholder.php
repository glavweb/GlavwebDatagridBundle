<?php

/*
 * This file is part of the Glavweb SecurityBundle package.
 *
 * (c) GLAVWEB <info@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\DatagridBundle\DataSchema;

use Doctrine\Common\Annotations\Reader;
use Glavweb\SecurityBundle\Mapping\Annotation\Access;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class Placeholder
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 * @package Glavweb\SecurityBundle
 */
class Placeholder
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var \Twig_Environment
     */
    private $twigEnvironment;

    /**
     * AccessHandler constructor.
     *
     * @param Reader $annotationReader
     * @param TokenStorageInterface $tokenStorage
     * @param SecurityExtension $securityExtension
     */
    public function __construct(Reader $annotationReader, TokenStorageInterface $tokenStorage, SecurityExtension $securityExtension)
    {
        $this->tokenStorage = $tokenStorage;

        $this->twigEnvironment = new \Twig_Environment(new \Twig_Loader_Array([]), [
            'strict_variables' => true,
            'autoescape'       => false,
        ]);
        $this->twigEnvironment->addExtension($securityExtension);
    }

    /**
     * @param string $condition
     * @param string $alias
     * @param UserInterface $user
     * @return string
     */
    public function condition($condition, $alias, UserInterface $user = null)
    {
        if (!$user) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        $userId = null;
        if ($user instanceof UserInterface && method_exists($user, 'getId')) {
            $userId = $user->getId();
        }

        $template = $this->twigEnvironment->createTemplate($condition);

        return trim($template->render([
            'alias'  => $alias,
            'user'   => $user,
            'userId' => $userId,
        ]));
    }
}