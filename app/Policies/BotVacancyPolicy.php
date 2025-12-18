<?php

namespace App\Policies;

use App\Models\BotVacancy;
use App\Models\User;
use App\Services\ChannelAdminService;

class BotVacancyPolicy
{
    protected ChannelAdminService $adminService;

    public function __construct(ChannelAdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Determine if user can approve vacancies
     */
    public function approve(User $user, BotVacancy $vacancy): bool
    {
        // Only pending vacancies can be approved
        if (!$vacancy->isPending()) {
            return false;
        }

        // Check if user is a channel admin for management channel
        return $this->adminService->isFilamentUserAdminForManagementChannel($user->id);
    }

    /**
     * Determine if user can reject vacancies
     */
    public function reject(User $user, BotVacancy $vacancy): bool
    {
        // Only pending vacancies can be rejected
        if (!$vacancy->isPending()) {
            return false;
        }

        // Check if user is a channel admin for management channel
        return $this->adminService->isFilamentUserAdminForManagementChannel($user->id);
    }

    /**
     * Determine if user can view approval actions
     */
    public function viewApprovalActions(User $user): bool
    {
        return $this->adminService->isFilamentUserAdminForManagementChannel($user->id);
    }
}
