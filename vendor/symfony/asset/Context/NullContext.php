<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Asset\Context;

/**
 * A context that does nothing.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class NullContext implements ContextInterface
{
    public function getBasePath(): string
    {
        return '';
    }

    public function isSecure(): bool
    {
        return false;
    }
}
