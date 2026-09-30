<?php

namespace App\Http\Controllers;

use App\Enums\LeadPlatform;
use App\Http\Requests\BulkLeadRequest;
use App\Http\Requests\BulkLeadStatusRequest;
use App\Http\Requests\LeadFilterRequest;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    /**
     * Show the public landing page.
     */
    public function showLanding()
    {
        return Inertia::render('Landing');
    }

    /**
     * Handle the public landing page form submission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:2000',
        ]);

        $validated['status'] = 'new';
        $validated['source'] = 'landing_page';
        $validated['platform'] = LeadPlatform::Website;

        Lead::create($validated);

        return back()->with('success', 'Thank you! We will contact you soon.');
    }

    /**
     * Store a lead entered manually by an admin (e.g. from Upwork or Fiverr).
     */
    public function storeManual(LeadRequest $request)
    {
        Lead::create([
            ...$request->validated(),
            'source' => 'manual',
        ]);

        return back()->with('success', 'Lead added.');
    }

    /**
     * Show the admin leads list, searched, filtered, sorted and paginated on the server.
     */
    public function index(LeadFilterRequest $request)
    {
        $filters = $request->filters();

        // Remember the admin's page size for later visits that don't specify one.
        if ($request->filled('per_page')) {
            $request->session()->put('leads.per_page', $request->integer('per_page'));
        }

        $leads = Lead::query()
            ->when($filters['view'] === 'deleted', fn ($query) => $query->onlyTrashed())
            ->filter([...$filters, 'timezone' => $request->timezone()])
            ->orderBy($filters['sort'], $filters['direction'])
            ->orderBy('id', $filters['direction'])
            ->paginate($request->session()->get('leads.per_page', 10))
            ->withQueryString();

        // Deleting the last leads on the last page would otherwise leave the admin on an empty page.
        if ($leads->isEmpty() && $leads->currentPage() > 1) {
            return redirect()->to($leads->url($leads->lastPage()));
        }

        return Inertia::render('Admin/Leads', [
            'leads' => $leads,
            'filters' => $filters,
            'counts' => [
                'active' => Lead::count(),
                'deleted' => Lead::onlyTrashed()->count(),
            ],
        ]);
    }

    /**
     * Show one lead with its notes history. Deleted leads can be viewed too, read-only.
     */
    public function show(Request $request, Lead $lead)
    {
        $user = $request->user();

        $notes = $lead->notes()
            ->with('user:id,name')
            ->get()
            ->map(fn (LeadNote $note) => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->user?->name,
                'created_at' => $note->created_at,
                'can_delete' => $user->can('delete', $note),
            ]);

        return Inertia::render('Admin/LeadShow', [
            'lead' => $lead,
            'notes' => $notes,
        ]);
    }

    /**
     * Download active leads as a CSV file, limited to the given IDs or the
     * same search, filters and sort the admin has applied on the Leads page.
     */
    public function export(LeadFilterRequest $request): StreamedResponse
    {
        $filters = $request->filters();

        $leads = Lead::query()
            ->when($request->validated('ids'), fn ($query, array $ids) => $query->whereKey($ids))
            ->filter([...$filters, 'timezone' => $request->timezone()])
            ->orderBy($filters['sort'] === 'deleted_at' ? 'created_at' : $filters['sort'], $filters['direction'])
            ->orderBy('id', $filters['direction'])
            ->lazy();

        // Received dates in the admin's own time zone, matching the Leads page.
        $zone = $request->timezone();

        return response()->streamDownload(function () use ($leads, $zone) {
            $out = fopen('php://output', 'w');

            // Byte-order mark so Excel reads the file as UTF-8.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['ID', 'Name', 'Email', 'Company', 'Phone', 'Platform', 'Status', 'Source', 'Message', 'Received']);

            foreach ($leads as $lead) {
                fputcsv($out, array_map($this->csvSafe(...), [
                    $lead->id,
                    $lead->name,
                    $lead->email,
                    $lead->company,
                    $lead->phone,
                    $lead->platform->label(),
                    Str::headline($lead->status),
                    Str::headline($lead->source),
                    $lead->message,
                    $lead->created_at->setTimezone($zone)->toDateTimeString(),
                ]));
            }

            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Stop spreadsheet apps from running values as formulas, since lead data comes from the public form.
     */
    private function csvSafe(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Update a lead's details from the admin panel.
     */
    public function update(LeadRequest $request, Lead $lead)
    {
        $lead->update($request->validated());

        return back()->with('success', 'Lead updated.');
    }

    /**
     * Move a lead to the deleted list, from where it can be restored.
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();

        return back()->with('success', 'Lead moved to Deleted.');
    }

    /**
     * Restore a deleted lead.
     */
    public function restore(Lead $lead)
    {
        $lead->restore();

        return back()->with('success', 'Lead restored.');
    }

    /**
     * Permanently delete a lead that is already in the deleted list.
     */
    public function forceDestroy(Lead $lead)
    {
        abort_unless($lead->trashed(), 404);

        $lead->forceDelete();

        return back()->with('success', 'Lead permanently deleted.');
    }

    /**
     * Move several leads to the deleted list at once.
     */
    public function bulkDestroy(BulkLeadRequest $request)
    {
        $count = Lead::whereKey($request->ids())->delete();

        return back()->with('success', trans_choice(':count lead moved to Deleted.|:count leads moved to Deleted.', $count));
    }

    /**
     * Set the same status on several leads at once.
     */
    public function bulkUpdateStatus(BulkLeadStatusRequest $request)
    {
        $count = Lead::whereKey($request->ids())->update([
            'status' => $request->validated('status'),
        ]);

        return back()->with('success', trans_choice(':count lead updated.|:count leads updated.', $count));
    }

    /**
     * Restore several deleted leads at once.
     */
    public function bulkRestore(BulkLeadRequest $request)
    {
        $count = Lead::onlyTrashed()->whereKey($request->ids())->restore();

        return back()->with('success', trans_choice(':count lead restored.|:count leads restored.', $count));
    }

    /**
     * Permanently delete several leads that are already in the deleted list.
     */
    public function bulkForceDestroy(BulkLeadRequest $request)
    {
        $count = Lead::onlyTrashed()->whereKey($request->ids())->forceDelete();

        return back()->with('success', trans_choice(':count lead permanently deleted.|:count leads permanently deleted.', $count));
    }

    /**
     * Update a lead's status from the admin panel.
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,follow_up,won,lost',
        ]);

        $lead->update($validated);

        return back();
    }
}
