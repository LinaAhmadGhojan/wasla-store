<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order)
    {
        return $user->id === $order->user_id || $user->id === $order->vendor->user_id || $user->role_id === optional($user->role)->id;
    }

    public function update(User $user, Order $order)
    {
        return $user->id === $order->vendor->user_id || $user->role_id === optional($user->role)->id;
    }
}
