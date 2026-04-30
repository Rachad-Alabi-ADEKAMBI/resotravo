<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Google OAuth -->
    <a href="{{ route('auth.google.redirect') }}?role=client"
       style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:10px 16px;border:1.5px solid #e5e7eb;border-radius:8px;background:#fff;font-size:.9rem;font-weight:600;color:#374151;text-decoration:none;margin-bottom:18px;transition:background .2s"
       onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
        <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.14 0 5.95 1.08 8.17 2.85l6.1-6.1C34.46 3.39 29.5 1.5 24 1.5 14.81 1.5 6.97 6.96 3.13 14.72l7.11 5.52C12.04 14.1 17.56 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24c0-1.64-.15-3.22-.42-4.75H24v9h12.7c-.55 2.98-2.19 5.5-4.66 7.19l7.2 5.59C43.32 37.09 46.5 30.97 46.5 24z"/><path fill="#FBBC05" d="M10.24 28.24A14.44 14.44 0 0 1 9.5 24c0-1.47.25-2.89.7-4.23l-7.11-5.52A22.46 22.46 0 0 0 1.5 24c0 3.68.88 7.15 2.44 10.22l6.3-5.98z"/><path fill="#34A853" d="M24 46.5c5.5 0 10.13-1.82 13.51-4.94l-7.2-5.59c-1.84 1.24-4.2 1.97-6.31 1.97-6.44 0-11.96-4.6-13.76-10.74l-6.3 5.98C6.97 41.04 14.81 46.5 24 46.5z"/><path fill="none" d="M0 0h48v48H0z"/></svg>
        Se connecter avec Google
    </a>

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
        <hr style="flex:1;border:none;border-top:1px solid #e5e7eb">
        <span style="font-size:.8rem;color:#9ca3af">ou par email</span>
        <hr style="flex:1;border:none;border-top:1px solid #e5e7eb">
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
