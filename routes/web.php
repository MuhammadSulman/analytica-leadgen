<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadNoteController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeadController::class, 'showLanding'])->name('landing');
Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/admin/leads', [LeadController::class, 'index'])->name('admin.leads');
    Route::get('/admin/leads/export', [LeadController::class, 'export'])->name('admin.leads.export');
    Route::post('/admin/leads', [LeadController::class, 'storeManual'])->name('admin.leads.store');
    // Bulk routes come before the {lead} routes so "bulk" isn't read as a lead ID.
    Route::delete('/admin/leads/bulk', [LeadController::class, 'bulkDestroy'])->name('admin.leads.bulk-destroy');
    Route::patch('/admin/leads/bulk/status', [LeadController::class, 'bulkUpdateStatus'])->name('admin.leads.bulk-status');
    Route::patch('/admin/leads/bulk/restore', [LeadController::class, 'bulkRestore'])->name('admin.leads.bulk-restore');
    Route::delete('/admin/leads/bulk/force', [LeadController::class, 'bulkForceDestroy'])->name('admin.leads.bulk-force-destroy');

    Route::get('/admin/leads/{lead}', [LeadController::class, 'show'])
        ->withTrashed()
        ->name('admin.leads.show');
    Route::put('/admin/leads/{lead}', [LeadController::class, 'update'])->name('admin.leads.update');
    Route::delete('/admin/leads/{lead}', [LeadController::class, 'destroy'])->name('admin.leads.destroy');
    Route::patch('/admin/leads/{lead}/restore', [LeadController::class, 'restore'])
        ->withTrashed()
        ->name('admin.leads.restore');
    Route::delete('/admin/leads/{lead}/force', [LeadController::class, 'forceDestroy'])
        ->withTrashed()
        ->name('admin.leads.force-destroy');
    Route::patch('/admin/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('admin.leads.status');

    Route::post('/admin/leads/{lead}/notes', [LeadNoteController::class, 'store'])->name('admin.leads.notes.store');
    Route::delete('/admin/leads/{lead}/notes/{note}', [LeadNoteController::class, 'destroy'])
        ->scopeBindings()
        ->name('admin.leads.notes.destroy');
});

require __DIR__.'/auth.php';
