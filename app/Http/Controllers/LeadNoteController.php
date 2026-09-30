<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadNoteRequest;
use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Support\Facades\Gate;

class LeadNoteController extends Controller
{
    /**
     * Add a note to a lead's history.
     */
    public function store(StoreLeadNoteRequest $request, Lead $lead)
    {
        $note = $lead->notes()->make($request->validated());
        $note->user()->associate($request->user());
        $note->save();

        return back()->with('success', 'Note added.');
    }

    /**
     * Remove a note (its author or a super admin only).
     */
    public function destroy(Lead $lead, LeadNote $note)
    {
        Gate::authorize('delete', $note);

        $note->delete();

        return back()->with('success', 'Note deleted.');
    }
}
