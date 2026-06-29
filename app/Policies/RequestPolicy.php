<?php

namespace App\Policies;

use App\Models\Request as LoanRequest;
use App\Models\User;

class RequestPolicy
{
    public function view(User $user, LoanRequest $request): bool
    {
        return $user->isAdmin() || $request->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function decide(User $user, LoanRequest $request): bool
    {
        return $user->isAdmin() && $request->status === 'Pending';
    }

    public function checkOut(User $user, LoanRequest $request): bool
    {
        return $user->isAdmin() && $request->status === 'Approved';
    }

    public function checkIn(User $user, LoanRequest $request): bool
    {
        return $user->isAdmin() && $request->status === 'Checked Out';
    }

    public function requestExtension(User $user, LoanRequest $request): bool
    {
        return $request->user_id === $user->id
            && $request->status === 'Checked Out'
            && ! $request->extension;
    }

    public function reportDamage(User $user, LoanRequest $request): bool
    {
        return $request->user_id === $user->id
            && $request->status === 'Checked Out'
            && ! $request->damageReport;
    }

    public function remind(User $user, LoanRequest $request): bool
    {
        return $user->isAdmin() && $request->isOverdue();
    }

    public function resolveExtension(User $user, LoanRequest $request): bool
    {
        return $user->isAdmin() && $request->status === 'Checked Out';
    }
}
