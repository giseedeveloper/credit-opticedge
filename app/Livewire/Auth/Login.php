<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Sign in')]
class Login extends Component
{
    public string $method = 'email';

    public string $login_identifier = '';

    public string $password = '';

    public bool $showPassword = false;

    public bool $remember = false;

    public function mount()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->firstAccessibleRouteName());
        }
    }

    public function swapMethod($newMethod)
    {
        $this->method = $newMethod;
        $this->login_identifier = '';
        $this->resetValidation();
    }

    public function togglePassword()
    {
        $this->showPassword = ! $this->showPassword;
    }

    public function authenticate()
    {
        $this->validate([
            'login_identifier' => 'required',
            'password' => 'required',
        ]);

        $ip = request()->ip();
        $rateLimitKey = 'web_login_attempts:'.$ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            activity('security')
                ->withProperties(['ip' => $ip, 'identifier' => $this->login_identifier])
                ->log("Web Brute Force Prevention: Locked out IP {$ip} for {$seconds} seconds.");

            throw ValidationException::withMessages([
                'login_identifier' => "Too many attempts. Locked for {$seconds} seconds.",
            ]);
        }

        $loginId = $this->login_identifier;
        $field = $this->method === 'phone' ? 'phone' : 'email';

        if ($field === 'phone') {
            $phone = preg_replace('/[^0-9]/', '', $loginId);
            if (str_starts_with($phone, '0')) {
                $phone = '255'.substr($phone, 1);
            } elseif (strlen($phone) == 9) {
                $phone = '255'.$phone;
            }
            $loginId = $phone;
        }

        if (Auth::attempt([$field => $loginId, 'password' => $this->password])) {
            if (! Auth::user()->is_active) {
                RateLimiter::hit($rateLimitKey, 60);
                Auth::logout();

                throw ValidationException::withMessages([
                    'login_identifier' => 'Your account has been deactivated. Contact your administrator.',
                ]);
            }

            RateLimiter::clear($rateLimitKey);

            activity('security')
                ->performedOn(Auth::user())
                ->causedBy(Auth::user())
                ->withProperties(['ip' => $ip])
                ->log('Successful Web Login (Secure Console).');

            session()->regenerate();

            return redirect()->route(Auth::user()->firstAccessibleRouteName());
        }

        RateLimiter::hit($rateLimitKey, 60);

        activity('security')
            ->withProperties(['ip' => $ip, 'identifier' => $this->login_identifier])
            ->log('Failed Web Login Attempt via Secure Console.');

        $this->addError('login_identifier', 'These credentials do not match our records.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
