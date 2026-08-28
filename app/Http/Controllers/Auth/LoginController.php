<?php



namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
use App\Models\StaffAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('auth_user')) {
            $role = $request->session()->get('auth_user.role');
            return redirect()->route($role === 'admin' ? 'admin.dashboard' : 'staff.dashboard');
        }

        return view('loginpage');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $turnstileResponse = $request->input('cf-turnstile-response');
        $secretKey = config('services.cloudflare.turnstile_secret_key');

        if (!$this->verifyTurnstile($turnstileResponse, $secretKey, $request->ip(), $request->getHost())) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['cf-turnstile-response' => 'Security verification failed. Please complete the captcha.'])
                ->with('error', 'Security verification failed. Please verify that you are not a robot.');
        }

        $account = AdminAccount::where('email', $credentials['email'])->first();
        $role = null;

        if ($account && Hash::check($credentials['password'], $account->password)) {
            $role = 'admin';
        } else {
            $account = StaffAccount::where('email', $credentials['email'])->first();
            if ($account && Hash::check($credentials['password'], $account->password)) {
                if ($account->ban_status) {
                    return back()
                        ->withInput($request->only('email'))
                        ->with('error', 'This staff account has been banned.');
                }
                $role = 'staff';
            }
        }

        if (!$role || !$account) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Invalid email or password.');
        }

        session(['auth_user' => [
            'id' => $account->id,
            'name' => $account->name,
            'email' => $account->email,
            'role' => $role,
        ]]);

        return redirect()->route($role === 'admin' ? 'admin.dashboard' : 'staff.dashboard');
    }

    /**
     * Verify Cloudflare Turnstile token with Cloudflare's siteverify endpoint.
     */
    protected function verifyTurnstile(?string $response, ?string $secretKey, ?string $ip, ?string $host = null): bool
    {
        // Bypass in testing environment unless strict testing is enabled
        if (app()->environment('testing') && !config('services.cloudflare.test_turnstile_strict', false)) {
            return true;
        }

        $isLocalhost = in_array($host ?? request()->getHost(), ['localhost', '127.0.0.1', '::1']);

        // When running on localhost, use Cloudflare's official dummy test secret key
        if ($isLocalhost && (env('CLOUDFLARE_TURNSTILE_USE_TEST_ON_LOCAL', true) || str_starts_with($response ?? '', '2x000000000000000000002') || $response === 'XXXX.DUMMY.TOKEN.XXXX')) {
            $secretKey = '1x0000000000000000000000000000000AA';
        }

        if (empty($secretKey)) {
            return true;
        }

        if (empty($response)) {
            return false;
        }

        try {
            $verifyResponse = Http::asForm()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $response,
                    'remoteip' => $ip,
                ]);

            return (bool) $verifyResponse->json('success');
        } catch (\Throwable $e) {
            Log::error('Cloudflare Turnstile verification failed: ' . $e->getMessage());
            return $isLocalhost;
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('auth_user');
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}


