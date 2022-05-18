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
use Carbon\Carbon;

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
			$user_id = $isValidUser["data"]["user_id"];
            $tests = Test::where("user_id",$user_id)->get();
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
				$is_correct = ($test_question->correct_answer == $request->user_answer_number)?1:0;
                if($is_correct){
                    Test::where('test_id',$test_question->test_id)->increment('correct', 1, ['updated_at' => Carbon::now()]);
                }else{
                    Test::where('test_id',$test_question->test_id)->increment('incorrect', 1, ['updated_at' => Carbon::now()]);
                }
				$data = TestQuestion::where("test_questions_id",$quiz_id)->update(['user_answer_number'=>$request->user_answer_number,'is_correct'=>$is_correct]);
				return Response::json(["success" => true, "data" => $is_correct]);
			}else{
				return Response::json(["success" => false, "data" => "question not found"], 404);
			}
            
        } else {
            return Response::json($isValidUser);
        }
    }
    /* Test Methods */


    /* Statistics Methods */
    public function getStateByUserId(Request $request)
    {
        $isValidUser = $this->isValidUser($request);
        $result = array();
        $elemx = array();
        $elemx['01'] = "Jan";
		$elemx['02'] = "Feb";
		$elemx['03'] = "Mar";
		$elemx['04'] = "Apr";
		$elemx['05'] = "May";
		$elemx['06'] = "Jun";
		$elemx['07'] = "Jul";
		$elemx['08'] = "Aug";
		$elemx['09'] = "Sep";
		$elemx['10'] = "Oct";
		$elemx['11'] = "Nov";
		$elemx['12'] = "Dec";

        if ($isValidUser["success"] == true) {

            $user_id = $isValidUser["data"]["user_id"];

            $tests = Test::where("user_id",$user_id)->get();//all pass & fail
            $testsCount = $tests->count();
            $result['all'] = $testsCount;

            $practice = Test::where("type_id",1)->where("user_id",$user_id)->get();
            $practiceCount_pass = 0;
            $practiceCount_fail = 0;
            foreach($practice as $key=>$value){
				if($value['correct']>=9){
					$practiceCount_pass++;
				}else{
					$practiceCount_fail++;
				}
			}
            $result['practice']['pass'] = $practiceCount_pass;
            $result['practice']['fail'] = $practiceCount_fail;

            $mock = Test::where("type_id",2)->where("user_id",$user_id)->get();
			$mockCount_pass = 0;
            $mockCount_fail = 0;
            foreach($mock as $key=>$value){
				if($value['correct']>=43){
					$mockCount_pass++;
				}else{
					$mockCount_fail++;
				}
			}
            $result['mock']['pass'] = $mockCount_pass;
            $result['mock']['fail'] = $mockCount_fail;


            if($tests){
                $labels = [];
                $data = [];
                for ($i = 0; $i < 6; $i++) {
                    $labels[] = date('m', strtotime(-$i . 'month'));
                    $fromDate = Carbon::now()->subMonth($i)->startOfMonth()->toDateString(); 
                    $tillDate = Carbon::now()->subMonth($i)->endOfMonth()->toDateString();
                    $testData = Test::where("user_id",$user_id)->whereBetween('created_at',[$fromDate,$tillDate])->get();
                    $data[] = $testData->count();
                }

                $result['data']['labels']= array_reverse($labels);
                $result['data']['datasets']['data']= array_reverse($data);
                $result['data']['datasets']['color']= "rgba(134, 65, 244, 1)"; // optional
                $result['data']['datasets']['strokeWidth']= 2; // optional
                $result['data']['datasets']['legend'] =["Test Progress"];

            }
            return Response::json($result);
        } else {
            return Response::json($isValidUser);
        }
    }
    /* Statistics Methods */


    public function getTestCenters(Request $request)
    {
        
	    $timeout = 8;
        $url = "https://www.gov.uk/find-theory-test-centre";

        $validator = Validator::make($request->all(), [
            "postcode" => "required",
        ]);
		$data = array();

        if ($validator->fails()) {
            return Response::json([
                "success" => false,
                "data" => $validator->errors(),
            ]);
        } else {
            $data_string = json_encode(["postcode"=>$request->postcode]);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);            
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //The maximum number of seconds to allow cURL functions to execute
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                    
                'Content-Type: application/json',                                                                 
                'Content-Length: ' . strlen($data_string))
            ); 
            $http_respond = curl_exec($ch);
        
            
            $dom = new \DOMDocument();
            @$dom->loadHTML($http_respond);
            $centersList = $dom->getElementById('options');
            $titles = $centersList->getElementsByTagName('h3');
            $postcodes = $this->getElementsByClass($centersList,'span','postal-code');
            $addresses = $this->getElementsByClass($centersList,'span','street-address');
            $localities = $this->getElementsByClass($centersList,'span','locality');

            for( $i=0;$i<$titles->length;$i++ ) {
                $data[$i]['title'] =  $titles[$i]->textContent;
                $data[$i]['postcode'] =  $postcodes[$i]->textContent;
                $data[$i]['street_address'] =  $addresses[$i]->textContent;
                $data[$i]['locality'] =  $localities[$i]->textContent;
            }

            return Response::json(
                ["success" => true, "data" => $data],
                200
            );
        }


	}



    function getElementsByClass(&$parentNode, $tagName, $className) {
        $nodes=array();
    
        $childNodeList = $parentNode->getElementsByTagName($tagName);
        for ($i = 0; $i < $childNodeList->length; $i++) {
            $temp = $childNodeList->item($i);
            if (stripos($temp->getAttribute('class'), $className) !== false) {
                $nodes[]=$temp;
            }
        }
    
        return $nodes;
    }


}
