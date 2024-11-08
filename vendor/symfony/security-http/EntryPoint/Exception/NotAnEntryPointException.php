<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\EntryPoint\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

/**
 * Thrown by generic decorators when a decorated authenticator does not implement
 * {@see AuthenticationEntryPointInterface}.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
#[WithHttpStatus(401)]
class NotAnEntryPointException extends \RuntimeException
{
}
