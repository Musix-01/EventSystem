<?php

namespace App\Http\Controllers;

use App\Rules\StrongPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $prefill = ['program' => null, 'year' => null, 'section' => null];
        if ($user->year_section && preg_match('/^(.+) (\d)-(\d)$/', $user->year_section, $m)) {
            $prefill = ['program' => $m[1], 'year' => $m[2], 'section' => $m[3]];
        }

        return view('profile.show', compact('user', 'prefill'));
    }


    public function updateClass(Request $request): RedirectResponse
    {
        abort_if($request->user()->isStaff(), 403);

        $data = $request->validate([
            'program' => ['required', Rule::in(config('campus.programs'))],
            'year'    => ['required', Rule::in(config('campus.years'))],
            'section' => ['required', Rule::in(config('campus.sections'))],
        ], [
            'program.required' => 'Choose your program.',
            'year.required'    => 'Choose your year level.',
            'section.required' => 'Choose your section.',
        ]);

        $request->user()->update(['year_section' => "{$data['program']} {$data['year']}-{$data['section']}"]);

        return back()->with('status', 'Year and section saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'confirmed', 'different:current_password', new StrongPassword()],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.different'                => 'Choose a password that is different from your current one.',
        ]);

        $request->user()->update([
            'password'             => $data['password'],  
            'must_change_password' => false,
        ]);

        $request->session()->regenerate();

        return redirect()->route('profile.show')->with('status', 'Your password was changed.');
    }

    public function updateCor(Request $request): RedirectResponse
    {
        abort_if($request->user()->isStaff(), 403);

        $request->validate([
            'cor' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'cor.required' => 'Choose your Certificate of Registration (COR).',
            'cor.mimes'    => 'The COR must be a PDF, JPG or PNG file.',
            'cor.max'      => 'The COR must be 5 MB or smaller.',
        ]);

        $user = $request->user();
        $old  = $user->cor_path;
        $path = $request->file('cor')->store('cor', 'local');

        $user->update(['cor_path' => $path, 'cor_verified_at' => null, 'cor_verified_by' => null]);

        if ($old && $old !== $path) {
            Storage::disk('local')->delete($old);
        }

        return back()->with('status', 'COR uploaded. The admin will verify it.');
    }
    public function cor(Request $request): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user->cor_path && Storage::disk('local')->exists($user->cor_path), 404, 'No COR on file.');

        return Storage::disk('local')->response($user->cor_path);
    }
}
