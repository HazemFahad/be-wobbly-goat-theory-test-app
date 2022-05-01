<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\VerificationController;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\UserController;
use App\Models\Question;
use App\Models\Category;
use App\Models\Answer;
use App\Models\User;
use App\Models\Test;
use App\Models\Statistics;
use App\Models\Article;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Category API//
Route::get('/categories', function () {
    return Category::all();
});
Route::get('/category/{id}', function ($id) {
    $category = Category::find($id);
    $result = ($category)?$category:['success' => false,"message" => "not found"];
    return $result;
});

//Category API//

//Question API//
Route::get('/questions', function () {
    return Question::paginate();
});
Route::get('/question/{id}', function ($id) {
    $question =  Question::find($id);
    if($question){
        $question['category_name'] = Category::findOrFail($question['category_id'])->category_name;
        $question['answers'] = Answer::where('question_id', $question['question_id'])->get();
    }
    $result = ($question)?$question:['success' => false,"message" => "not found"];
    return $result;
    /*
    return response()->json(
        $question ?? ["message" => "not found"], 
        404
    );
    */
});
//Question API//

//Category API//
Route::get('/answers', function () {
    return Answer::paginate();
});
Route::get('/answer/{id}', function ($id) {
    $category = Answer::find($id);
    $result = ($category)?$category:['success' => false,"message" => "not found"];
    return $result;
});
//Category API//



//Test API//
Route::post('/test/create/{type_id}',[ApiController::class, 'AddTestByTypeId']);

Route::get('/test/get/{test_id}',[ApiController::class, 'getTestByTestId']);

Route::post('/test/update/{test_id}',[ApiController::class, 'updateTestByTestId']);
//Test API//







//Articles API//
Route::get('articles', function() {
    // If the Content-Type and Accept headers are set to 'application/json', 
    // this will return a JSON structure. This will be cleaned up later.
    return Article::all();
});
 
Route::get('articles/{id}', function($id) {
    return Article::find($id);
});

Route::post('articles', function(Request $request) {
    return Article::create($request->all);
});

Route::put('articles/{id}', function(Request $request, $id) {
    $article = Article::findOrFail($id);
    $article->update($request->all());

    return $article;
});

Route::delete('articles/{id}', function($id) {
    Article::find($id)->delete();

    return 204;
});
//Articles API//





//Statistics API//

//Statistics API//


/**/

//User API//
Route::post('/user/signup',[ApiController::class, 'signup']);
Route::post('/user/signin',[ApiController::class, 'signin']);
//Route::post('/user/reset',[ApiController::class, 'reset']);
// forget password
Route::post('/user/forget', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'getResetToken']);
//reset password
Route::post('/user/password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset']);
//user verification
Route::get('/user/email/verify/{token}', [App\Http\Controllers\Auth\VerificationController::class, 'verify']);
//User API//




/* */
//404 API//
Route::any('{path}', function() {
    return response()->json([
        'success' => false,'message' => 'Route not found'
    ], 404);
})->where('path', '.*');
//404 API//
