<?php

namespace App\Policies;

use App\Models\Flow;
use App\Models\User;

class FlowPolicy
{
    /**
     * Determine if the user can view any flows.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their own flows
    }

    /**
     * Determine if the user can view the flow.
     */
    public function view(User $user, Flow $flow): bool
    {
        return $flow->user_id === $user->id;
    }

    /**
     * Determine if the user can create flows.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create flows
    }

    /**
     * Determine if the user can update the flow.
     */
    public function update(User $user, Flow $flow): bool
    {
        return $flow->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the flow.
     */
    public function delete(User $user, Flow $flow): bool
    {
        return $flow->user_id === $user->id;
    }

    /**
     * Determine if the user can toggle public status.
     */
    public function togglePublic(User $user, Flow $flow): bool
    {
        return $flow->user_id === $user->id;
    }
}
