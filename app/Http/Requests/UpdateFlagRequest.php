<?php

namespace App\Http\Requests;

use App\Models\Flag;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The same checks as adding a flag, except that keeping the flag's own name
 * does not count as a duplicate.
 */
class UpdateFlagRequest extends StoreFlagRequest
{
    /**
     * The flag being changed.
     */
    public function flag(): Flag
    {
        /** @var Flag */
        return $this->route('flag');
    }

    /**
     * @return HasMany<Flag, User>
     */
    protected function otherFlags(): HasMany
    {
        return parent::otherFlags()->whereKeyNot($this->flag()->id);
    }
}
