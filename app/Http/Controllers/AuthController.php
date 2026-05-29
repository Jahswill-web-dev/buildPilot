<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    // -----------------------------------------------------------------------
    // REGISTER
    // -----------------------------------------------------------------------

    /** Show the registration form. */
    public function showRegisterForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /** Validate the registration input, create the user, log them in. */
    public function register(Request $request)
    {
        $request->merge([
            'email' => strtolower((string) $request->input('email')),
        ]);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'], // cast to 'hashed' in User model
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Welcome to BuildPilot, '.$user->name.'!');
    }

    // -----------------------------------------------------------------------
    // LOGIN
    // -----------------------------------------------------------------------

    /** Show the login form. */
    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /** Validate credentials and attempt authentication. */
    public function login(Request $request)
    {
        $request->merge([
            'email' => strtolower((string) $request->input('email')),
        ]);

        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'))->with('success', 'Welcome back, '.Auth::user()->name.'!');
        }

        throw ValidationException::withMessages([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    // -----------------------------------------------------------------------
    // LOGOUT
    // -----------------------------------------------------------------------

    /** Log the current user out and invalidate the session. */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
