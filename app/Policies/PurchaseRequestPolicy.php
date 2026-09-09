<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
{
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->customer_id;
    }

    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->customer_id
            && $purchaseRequest->status === PurchaseRequest::STATUS_QUOTED;
    }

    public function reject(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->customer_id
            && $purchaseRequest->status === PurchaseRequest::STATUS_QUOTED;
    }

    public function pay(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->id === $purchaseRequest->customer_id
            && $purchaseRequest->status === PurchaseRequest::STATUS_CUSTOMER_APPROVED;
    }
}
