<?php declare(strict_types=1);

namespace App\Contracts;

interface LookupProviderInterface
{
    /**
     * Find a profile by username.
     *
     * @param string $username
     * @return array|null ['username' => string, 'id' => string, 'avatar' => string] or null if not found
     */
    public function findByUsername(string $username): ?array;

    /**
     * Find a profile by id.
     *
     * @param string $id
     * @return array|null ['username' => string, 'id' => string, 'avatar' => string] or null if not found
     */
    public function findByUserId(string $id): ?array;
}