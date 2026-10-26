<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    /**
     * Only the user who owns a category can change it. Anyone else gets a not
     * found page, so they cannot tell it exists.
     */
    public function update(User $user, Category $category): Response
    {
        return $category->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Only the user who owns a category can delete it, on the same terms as
     * changing it.
     */
    public function delete(User $user, Category $category): Response
    {
        return $this->update($user, $category);
    }
}
