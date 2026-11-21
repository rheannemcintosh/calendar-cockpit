<?php

namespace App\Models;

use Laravel\Passkeys\Passkey as BasePasskey;

/**
 * The package's passkey, with user_id cast to an integer.
 *
 * SQL Server returns BIGINT columns as strings, so without the cast the
 * package's ownership check (`$passkey->user_id === $user->getKey()`)
 * compares "1" to 1 and refuses to delete the user's own passkey.
 *
 * @property int $user_id
 */
class Passkey extends BasePasskey
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'user_id' => 'integer',
        ];
    }
}
