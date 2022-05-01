<?php

namespace App\Http\Controllers;
use App\Models\Question;
use App\Models\Category;
use App\Models\User;
use App\Models\Test;
use App\Models\Answer;
use Auth;
use Illuminate\Http\Request;
use Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;


use Illuminate\Support\Str;

class ApiController extends Controller
{
    //
    public function index()
    {
        $test['test'] = [
            '/api' => 'index',
        ];
        return $test;
    }

    /* User Methods */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:tblusers',
            'password' => 'required|min:8|max:255',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
        } else {
            $new_user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'active' => 1,
            ]);

            $new_user->save();
            return Response::json(
                ['success' => true, 'data' => $new_user],
                201
            );
        }
    }

    public function signin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8|max:255',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
        } else {
            $auth = Auth::attempt([
                'email' => $request->email,
                'password' => $request->password,
                'active' => 1,
            ]);
            if ($auth) {
                return Response::json(
                    ['success' => true, 'data' => $auth],
                    201
                );
            } else {

                return Response::json(
                    ['success' => false, 'data' => 'The username or password you provided is wrong or account not activated!'],
                    404
                );

            }
        }
     
    }

    public function reset_(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:tblusers',
        ]);

        // Delete all old code that user send before.
        User::where('email', $request->email)->delete();

        // Generate random code
        $data['code'] = mt_rand(100000, 999999);

        // Create a new code
        $codeData = User::create($data);

        // Send email to user
        Mail::to($request->email)->send(new SendCodeResetPassword($codeData->code));

        return response(['message' => trans('passwords.sent')], 200);
    }

    public function register_ (Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tblusers',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $request['password']=Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $user = User::create($request->toArray());
        $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        $response = ['token' => $token];
        return response($response, 200);
    }

    public function login_ (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $response = ['token' => $token];
                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'User does not exist'];
            return response($response, 422);
        }
    }
    public function logout_ (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
    /* User Methods */

    /* Test Methods */
    public function AddTestByTypeId()
    {
        $test['test'] = [
            '1' => 'ssss',
        ];
        return $test;
    }

    public function getTestByTestId()
    {
        $test['test'] = [
            '1' => 'ssss',
        ];
        return $test;
    }

    public function updateTestByTestId()
    {
        $test['test'] = [
            '1' => 'ssss',
        ];
        return $test;
    }
    /* Test Methods */
}
