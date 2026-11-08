<?php

namespace App\Policies;

use App\Models\Flag;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FlagPolicy
{
    /**
     * Only the user who owns a flag can change it. Anyone else gets a not
     * found page, so they cannot tell it exists.
     */
    public function update(User $user, Flag $flag): Response
    {
        return $flag->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Only the user who owns a flag can delete it, on the same terms as
     * changing it.
     */
    public function delete(User $user, Flag $flag): Response
    {
        return $this->update($user, $flag);
    }
}
