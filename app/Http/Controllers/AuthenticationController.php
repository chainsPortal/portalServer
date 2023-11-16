<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\network;
use App\Models\signer;
use App\Models\User;
use Str;
use Validator;
use DB;
class AuthenticationController extends Controller
{
    //
    public function addSigner(request $request){
        $validator = Validator::make($request->all(), [
            "signer" =>  "required|unique:signers"
      ]);

      if ($validator->fails()) {

           
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }

      $signer = new signer;
      $signer->signer = $request->signer;
      $signer->save();
      $response['code'] = 200;
      return response()->json($response ,200);
    }
    public function removeSigner(request $request){
        $validator = Validator::make($request->all(), [
            "signer" =>  "required|exists:signers"
      ]);

      if ($validator->fails()) {

           
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }

      $signer =  signer::where('signer' , $request->signer)->first();
      $signer->delete();
      $response['code'] = 200;
      return response()->json($response ,200);
    }
    public function register(request $request){
        $validator = Validator::make($request->all(), [
            "name" =>  "required",
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|'
      ]);

      if ($validator->fails()) {

           
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
      $user = new User;
      $recoverykey = Str::random(40);
      $user->remember_token = $recoverykey;
      $user->name = $request->name;
      $user->email = $request->email;
      $user->role ='user';
      $user->password = bcrypt($request->password);
       $user->save();
       $response['code'] = 200;
        return response()->json($response ,200);
    }
    public function login(request $request){
       
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
      ]);

      if ($validator->fails()) {
            $res['errors'] =$validator->messages()->all();
            $res['code'] = '404';
            return response()->json($res ,200);
      }
     
  $client = DB::table('oauth_clients')->where("name" , "Laravel Password Grant Client")->first();
    if(!$client){
        $res['message'] = "Authentication Client Not Available";
        $res['code'] = '404';
        return response()->json($res ,200);  
    }
    $data = [
        'grant_type'=> 'password',
        'client_id'=> $client->id,
        'client_secret'=> $client->secret,
        'username'=> $request->email,
        'password'=> $request->password,
        'scopes'=> '[*]'
    ];
    $request = Request::create('/oauth/token', 'POST', $data);
    return app()->handle($request);
    }
    public function adminAuthError(){
        $response['error'] = "User Not Permitted";
        $response['code']  = "404";
        return response()->json($response ,200);  
    }
    
}
