<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security;

use PhpParser\Builder\Param;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * @internal
 */
final class SecurityControllerBuilder
{
    public function addLoginMethod(ClassSourceManipulator $manipulator): void
    {
        $loginMethodBuilder = $manipulator->createMethodBuilder('login', 'Response', false);

        $loginMethodBuilder->addAttribute($manipulator->buildAttributeNode(Route::class, ['path' => '/login', 'name' => 'app_login']));

        $manipulator->addUseStatementIfNecessary(Response::class);
        $manipulator->addUseStatementIfNecessary(Route::class);
        $manipulator->addUseStatementIfNecessary(AuthenticationUtils::class);

        $loginMethodBuilder->addParam(
            (new Param('authenticationUtils'))->setType('AuthenticationUtils')
        );

        $manipulator->addMethodBody($loginMethodBuilder, <<<'CODE'
            <?php
            // if ($this->getUser()) {
            //     return $this->redirectToRoute('target_path');
            // }
            CODE
        );
        $loginMethodBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($loginMethodBuilder, <<<'CODE'
            <?php
            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();
            // last username entered by the user
            $lastUsername = $authenticationUtils->getLastUsername();
            CODE
        );
        $loginMethodBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($loginMethodBuilder, <<<'CODE'
            <?php
            return $this->render(
                'security/login.html.twig',
                [
                    'last_username' => $lastUsername,
                    'error' => $error,
                ]
            );
            CODE
        );
        $manipulator->addMethodBuilder($loginMethodBuilder);
    }

    public function addLogoutMethod(ClassSourceManipulator $manipulator): void
    {
        $logoutMethodBuilder = $manipulator->createMethodBuilder('logout', 'void', false);

        $logoutMethodBuilder->addAttribute($manipulator->buildAttributeNode(Route::class, ['path' => '/logout', 'name' => 'app_logout']));

        $manipulator->addUseStatementIfNecessary(Route::class);
        $manipulator->addMethodBody($logoutMethodBuilder, <<<'CODE'
            <?php
            throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
            CODE
        );
        $manipulator->addMethodBuilder($logoutMethodBuilder);
    }
}
