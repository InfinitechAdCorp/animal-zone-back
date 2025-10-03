<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use App\Models\User;

class VerifyEmailController extends Controller
{
    public function verify(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        // Check hash validity
        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            // Already verified
            return redirect(config('app.frontend_url') . '/login?verified=1');
        }

        // Mark as verified
        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect(config('app.frontend_url') . '/login?verified=1');
    }
}
 