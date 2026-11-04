<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LetterController extends Controller
{
    /** Admin inbox: every request letter sent by org officers. */
    public function index(): View
    {
        $letters = Letter::query()
            ->with(['event', 'sender'])
            ->latest()
            ->get();

        return view('admin.letters.index', compact('letters'));
    }

    /** The printable letter. Admins, and the officers of the hosting org. */
    public function show(Request $request, Letter $letter): View
    {
        $this->authorizeAccess($request, $letter);

        // Opening a letter counts as receiving it.
        if ($request->user()->isAdmin() && $letter->isUnread()) {
            $letter->update(['read_at' => now()]);
        }

        $letter->load(['event', 'sender']);

        return view('letters.show', compact('letter'));
    }

    /** The optional signed scan the officer uploaded. */
    public function attachment(Request $request, Letter $letter): StreamedResponse
    {
        $this->authorizeAccess($request, $letter);

        abort_unless($letter->attachment_path && Storage::disk('local')->exists($letter->attachment_path), 404);

        return Storage::disk('local')->response($letter->attachment_path);
    }

    private function authorizeAccess(Request $request, Letter $letter): void
    {
        $user = $request->user();

        $allowed = $user->isAdmin()
            || ($user->isOfficer() && $letter->event->organization === $user->organization);

        abort_unless($allowed, 403);
    }
}
