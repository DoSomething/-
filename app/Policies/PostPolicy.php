<?php

namespace Rogue\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Rogue\Models\Post;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the full post should be displayed.
     *
     * @param  Illuminate\Contracts\Auth\Authenticatable $user
     * @param  Rogue\Models\Post $post
     * @return bool
     */
    public function viewAll($user, Post $post)
    {
        return is_staff_user() || is_owner($post);
    }

    /**
     * Determine if the given post can be seen by the user.
     *
     * @param  Illuminate\Contracts\Auth\Authenticatable $user
     * @param  Rogue\Models\Post $post
     * @return bool
     */
    public function show(?Authenticatable $user, Post $post)
    {
        if ($post->status !== 'accepted') {
            return is_staff_user() || is_owner($post);
        }

        return true;
    }

    /**
     * Determine if the given post can be updated by the user.
     *
     * @param  Illuminate\Contracts\Auth\Authenticatable $user
     * @param  Rogue\Models\Post $post
     * @return bool
     */
    public function update($user, Post $post)
    {
        return is_staff_user() || is_owner($post);
    }

    /**
     * Determine if the given post can be reviewed by the user.
     *
     * @param  Illuminate\Contracts\Auth\Authenticatable $user
     * @param  Rogue\Models\Post $post
     * @return bool
     */
    public function review($user, Post $post)
    {
        return is_staff_user();
    }
}
