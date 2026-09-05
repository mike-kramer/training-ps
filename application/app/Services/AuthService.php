<?php

namespace App\Services;

use App\Contracts\AuditLogContract;
use App\Data\Auth\LoginData;
use App\Data\Auth\RegistrationData;
use App\Mail\EmailVerification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{

    public function __construct(
        readonly private AuditLogContract $auditServ
    )
    {

    }
    public function register(RegistrationData $data): User
    {
        $user = User::create([
            'email' => $data->email,
            'password' => $data->password,
        ]);
        event(new Registered($user));
        $this->auditServ->log("registration", $user->id);
        return $user;
    }

    public function login(LoginData $data, bool $useCookies = false): array
    {
        $user = User::where('email', $data->email)->firstOrFail();
        $user->checkPassword($data->password);
        $this->auditServ->log("logged-in", $user->id);

        if ($useCookies) {
            Auth::guard('web')->login($user);

            return [
                "success" => true,
                "auth" => "cookie",
            ];
        }

        $token = $user->createToken("login-token");

        return [
            "token" => $token->plainTextToken,
            "auth" => "token",
        ];
    }

    public function logout(Request $request): void
    {
        $user = $request->user();

        if ($user !== null) {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    public function sendVerificationEmail(User $user): void
    {
        $code = Str::random(6);
        Cache::put("mail-$code", $user->email, now()->addHour());
        Mail::to($user)->send(new EmailVerification($code));
        $this->auditServ->log("verification-code-sent", $user->id, parameters: ["email" => $user->email]);
    }

    public function verifyEmail(string $code): void
    {
        $email = Cache::get("mail-$code");
        $user = User::where('email', $email)->firstOrFail();
        $user->email_verified_at = now();
        $user->save();
    }
}
