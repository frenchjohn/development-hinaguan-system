<?php



namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;

use App\Models\AdminAccount;

use App\Models\StaffAccount;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

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



    public function logout(Request $request): RedirectResponse

    {

        $request->session()->forget('auth_user');

        $request->session()->regenerateToken();



        return redirect()->route('login');

    }

}

