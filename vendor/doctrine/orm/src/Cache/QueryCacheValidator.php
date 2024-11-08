<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

/**
 * Cache query validator interface.
 */
interface QueryCacheValidator
{
    /**
     * Checks if the query entry is valid
     */
    public function isValid(QueryCacheKey $key, QueryCacheEntry $entry): bool;
}
