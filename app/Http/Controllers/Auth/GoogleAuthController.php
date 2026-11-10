<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateGoogleUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse|SymfonyRedirectResponse
    {
        $intended = $request->query('redirect');

        if (is_string($intended) && str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            $request->session()->put('url.intended', $intended);
        }

        if (! filled(config('services.google.client_id')) || ! filled(config('services.google.client_secret'))) {
            return to_route('login')->withErrors([
                'google' => 'Falta configurar el secreto web de Google Cloud.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, AuthenticateGoogleUser $authenticate): RedirectResponse
    {
        try {
            $user = $authenticate->execute(Socialite::driver('google')->user());
        } catch (\Throwable $exception) {
            report($exception);

            return to_route('login')->withErrors([
                'google' => $exception instanceof ValidationException
                    ? $exception->validator->errors()->first('google')
                    : 'No se pudo iniciar sesión con Google. Inténtalo nuevamente.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('catalogo'));
    }
}
