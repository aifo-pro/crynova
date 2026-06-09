<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMerchant() && $user->merchant !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->merchant && $apiKey->merchant_id === $user->merchant->id;
    }
}
