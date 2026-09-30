<?php

namespace App\Policies;

use App\Models\LeadNote;
use App\Models\User;

class LeadNotePolicy
{
    /**
     * Admins can remove their own notes; a super admin can remove anyone's.
     */
    public function delete(User $user, LeadNote $note): bool
    {
        return $user->isSuperAdmin() || $note->user_id === $user->id;
    }
}
