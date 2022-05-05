<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;


class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;


    public function __construct()
    {
        $this->middleware('guest');
    }

    public function getResetToken(Request $request)
    {
		$validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()){
                return response()->json([
                    "success" => false,
                    "data" => $validator->errors(),
                ]);
		}else{
			if (User::where('email', $request->email)->exists()) {
			$sent = $this->sendResetLinkEmail($request);

			return ($sent) 
				? response()->json(['success' => true,"data"=>'your password confirmation email has been sent successfully!'])
				: response()->json(['success' => false,"data"=>'Failed to connect smtp server']);
			}else{
				return response()->json(['success' => false,"data"=>['email'=>['email not found']]]);
			}
		}
    }

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);
        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );
		return $response?true:false ;
        //return $response == Password::RESET_LINK_SENT ? 1 : 0;
    }


}
