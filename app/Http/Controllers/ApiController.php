<?php

namespace App\Http\Controllers;
use App\Models\Question;
use App\Models\Category;
use App\Models\User;
use App\Models\Test;
use App\Models\TestQuestion;
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
        $endpoints = json_decode(file_get_contents(storage_path() . "/endpoints.json"), true);
        return $endpoints;
    }

    public function isValidUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required|min:8|max:255",
        ]);

        if ($validator->fails()) {
            return ["success" => false,"data" => $validator->errors()];
        } else {
            $auth = Auth::attempt([
                "email" => $request->email,
                "password" => $request->password,
                "active" => 1,
            ]);
            if ($auth) {
                $data = array(
                    "user_id"=> Auth::user()->user_id,
                    "name"=> Auth::user()->name,
                    "email"=> Auth::user()->email,
                );
                return ["success" => true, "data" => $data];
            } else {
                return ["success" => false, "data" => ['email'=>["The username or password you provided is wrong or account not activated!"]]];
            }
        }
    }

    /* User Methods */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required|email|unique:tblusers",
            "password" => "required|min:8|max:255|confirmed",
            "password_confirmation" => "required|min:8|max:255"
        ]);

        if ($validator->fails()) {
            return Response::json([
                "success" => false,
                "data" => $validator->errors(),
            ]);
        } else {
            $new_user = User::create([
                "name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
                "active" => 1,
            ]);

            $new_user->save();
            return Response::json(
                ["success" => true, "data" => $new_user],
                201
            );
        }
    }

    public function signin(Request $request)
    {
        $isValidUser = $this->isValidUser($request);
        return Response::json($isValidUser);
    }

    public function changePassword(Request $request)
    {

        $isValidUser = $this->isValidUser($request);

        if ($isValidUser["success"] == true) {
            $validator = Validator::make($request->all(), [
                "password_new" => "required|min:8|max:255|confirmed",
                "password_confirmation" => "required|min:8|max:255"
            ]);
            if ($validator->fails()) {
                return Response::json([
                    "success" => false,
                    "data" => $validator->errors(),
                ]);
            } else {
                $user = User::where("email", $request->email)->first();
                if ($user) {
                    $user->password = Hash::make($request->password_new);
                    $user->save();                
                    return Response::json(
                        ["success" => true, "data" => $user],
                        201
                    );
                } else {
                    return Response::json(
                        ["success" => false, "data" => ['email'=>["The email you provided is not exists or account not activated yet!"]]],
                        404
                    );
                }

            }

        } else {
            return Response::json($isValidUser);
        }
     
    }

    public function reset_(Request $request)
    {
        $data = $request->validate([
            "email" => "required|email|exists:tblusers",
        ]);

        // Delete all old code that user send before.
        User::where("email", $request->email)->delete();

        // Generate random code
        $data["code"] = mt_rand(100000, 999999);

        // Create a new code
        $codeData = User::create($data);

        // Send email to user
        Mail::to($request->email)->send(new SendCodeResetPassword($codeData->code));

        return response(["data" => trans("passwords.sent")], 200);
    }

    public function register_ (Request $request) {
        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:tblusers",
            "password" => "required|string|min:6|confirmed",
        ]);
        if ($validator->fails())
        {
            return response(["errors"=>$validator->errors()->all()], 422);
        }
        $request["password"]=Hash::make($request["password"]);
        $request["remember_token"] = Str::random(10);
        $user = User::create($request->toArray());
        $token = $user->createToken("Laravel Password Grant Client")->accessToken;
        $response = ["token" => $token];
        return response($response, 200);
    }

    public function login_ (Request $request) {
        $validator = Validator::make($request->all(), [
            "email" => "required|string|email|max:255",
            "password" => "required|string|min:6|confirmed",
        ]);
        if ($validator->fails())
        {
            return response(["errors"=>$validator->errors()->all()], 422);
        }
        $user = User::where("email", $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken("Laravel Password Grant Client")->accessToken;
                $response = ["token" => $token];
                return response($response, 200);
            } else {
                $response = ["date" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["date" =>"User does not exist"];
            return response($response, 422);
        }
    }
    public function logout_ (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ["data" => "You have been successfully logged out!"];
        return response($response, 200);
    }
    /* User Methods */

    /* Test Methods */

    public function getTests(Request $request)
    {
        $isValidUser = $this->isValidUser($request);

        if ($isValidUser["success"] == true) {
            $tests = Test::all();
            return Response::json(["data"=>$tests]);
        } else {
            return Response::json($isValidUser);
        }

    }
    
    public function createTestByTypeId(Request $request)
    {
        $isValidUser = $this->isValidUser($request);

        if ($isValidUser["success"] == true) {
            $limit = ($request->type_id==2)?50:10;
            $user_id = $isValidUser["data"]["user_id"];
            $type_id = ($request->type_id)?$request->type_id:1;
            $categories = is_array($request->categories)?$request->categories:[];

            $test = new Test;
            $test->user_id = $user_id;
            $test->type_id = $type_id;
    
            $test->save();
            $test_id = $test->test_id;

            $data = array();
            if(count($categories)>0){
                $questions = Question::whereIn("category_id",$categories)->inRandomOrder()->limit($limit)->get();
            }else{
                $questions = Question::inRandomOrder()->limit($limit)->get();
            }
            
            foreach($questions as $key=>$value){
                $test_question = new TestQuestion;
                $test_question->test_id = $test_id;
                $test_question->question_id = $value["question_id"];
                $test_question->correct_answer = $value["correct_answer"];        
                $test_question->save();
                $value["test_questions_id"]= $test_question->test_questions_id;
                $value["correct_answer"]= $test_question->correct_answer;
                $value["test_id"]= $test_question->test_id;
                $value["category_name"] =  Category::find($value["category_id"])->category_name;
                $value["answers"] = Answer::where("question_id",$value["question_id"])->get();
                $data[] = $value; 
            }

				$result = array();
				$result["test_id"]= $test->type_id;
				$result["type_id"]= $test->type_id;
				$result["user_id"]= $test->user_id;
				$result["created_at"]= $test->created_at;
				$result["data"]=$data;

				return Response::json($result);
		} else {
            return Response::json($isValidUser);
        }
    }

    public function getTestByTestId(Request $request,$test_id = 0)
    {
        $isValidUser = $this->isValidUser($request);

        if ($isValidUser["success"] == true) {
            $user_id = $isValidUser["data"]["user_id"];

            $test = Test::where("test_id",$test_id)->where("test_id",$test_id)->first();
            if($test){
				$test_questions = TestQuestion::where("test_id",$test_id)->get();
				$data = array();
				foreach($test_questions as $key=>$value){

					$question= Question::where("question_id",$value["question_id"])->first();

					$value["question_id"] =  $question->question_id;
					$value["category_id"] =  $question->category_id;
					$value["category_name"] =  Category::find($question->category_id)->category_name;
					$value["media"] =  $question->media;
					$value["question"] =  $question->question;
					$value["explanation"] =  $question->explanation;
					$value["correct_answer"] =  $question->correct_answer;
					
					$value["answers"] = Answer::where("question_id",$value["question_id"])->get();
					$data[] = $value; 
				}
				$result = array();
				$result["test_id"]= $test["test_id"];
				$result["type_id"]= $test["type_id"];
				$result["user_id"]= $test["user_id"];
				$result["result"]= $test["result"];
				$result["created_at"]= $test["created_at"];
				$result["data"]=$data;

				return Response::json($result);
			}else{
                return Response::json(["success" => false, "data" => "test not found"], 404);
			}
        } else {
            return Response::json($isValidUser);
        }
    }

    public function updateTestByQuizId(Request $request,$quiz_id)
    {
        $isValidUser = $this->isValidUser($request);

        if ($isValidUser["success"] == true) {
			$test_question = TestQuestion::where("test_questions_id",$quiz_id)->first();
			if($test_question && is_numeric($request->user_answer_number)){
				$is_correct = ($request->is_correct == $request->user_answer_number)?1:0;
				$data = TestQuestion::where("test_questions_id",$quiz_id)->update(['user_answer_number'=>$request->user_answer_number,'is_correct'=>$is_correct]);
				return Response::json(["success" => true, "data" => true]);
			}else{
				return Response::json(["success" => false, "data" => "question not found"], 404);
			}
            
        } else {
            return Response::json($isValidUser);
        }
    }
    /* Test Methods */


    /* Statistics Methods */
    public function getStateByUserId(Request $request,$test_id)
    {
        $isValidUser = $this->isValidUser($request);

        if ($isValidUser["success"] == true) {
            return Response::json($isValidUser);
        } else {
            return Response::json($isValidUser);
        }
    }
    /* Statistics Methods */


}
