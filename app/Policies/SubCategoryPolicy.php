<?php

namespace App\Policies;

use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubCategoryPolicy
{
    /**
     * Only the user who owns a sub-category's category can change it. Anyone
     * else gets a not found page, so they cannot tell it exists.
     */
    public function update(User $user, SubCategory $subCategory): Response
    {
        return $subCategory->category->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Only the user who owns a sub-category's category can delete it, on the
     * same terms as changing it.
     */
    public function delete(User $user, SubCategory $subCategory): Response
    {
        return $this->update($user, $subCategory);
    }
}
