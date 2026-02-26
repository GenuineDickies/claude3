<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Any authenticated user can view tickets.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return true;
    }

    /**
     * Any authenticated user can create tickets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can update tickets.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return true;
    }

    /**
     * Only the user who created the ticket can delete it (placeholder).
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return false; // Not yet implemented
    }
}
