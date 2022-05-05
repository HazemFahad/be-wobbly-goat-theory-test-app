<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;

use App\Transformers\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\RedirectResponse;


class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;



    public function __construct()
    {
        $this->middleware('guest');
    }

    public function reset(Request $request)
    {
        $this->validate($request, $this->rules(), $this->validationErrorMessages());
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );
        if ($response == Password::PASSWORD_RESET) {
			if($request->wantsJson()) {
                return response()->json(["success" => true,'data'=>trans('passwords.reset')]);
            }else{
				return  new RedirectResponse(env("yourdomain"));
				//$response? $this->sendResetResponse($response)
				//: $this->sendResetFailedResponse($request, $response);
				//return  new RedirectResponse(env("yourdomain")+"?verified=$response");
			}
        }else{
			if($request->wantsJson()) {
                return response()->json(["success" => false,'email' => $request->input('email'), 'data'=>trans($response)]);
            }else{
				return  new RedirectResponse(env("yourdomain"));
			}
        }
    }


}
