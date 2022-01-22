<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;
use Auth;
use App\User;
use Hash;

class ProfileController extends Controller
{
    public $successStatus = 200;
    public $errorValidation = 422;
    public $errorStatus = 400;


    /**
     * method used to validate signup details and create account.
     * **/
    public function signup(Request $request){
        $validator = Validator::make($request->all(), [
            'name'=>'bail|required|min:3|max:20',
            'email'=>'bail|required|email|unique:users,email|max:50',
            'loan_amount'=>'bail|required|numeric|min:1000',
            'loan_term'=>'bail|required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response(['message'=>$validator->errors()->first(),'statuscode'=>$this->errorStatus,'status'=>false], $this->errorStatus);
        }
        DB::beginTransaction();
        try{
            $weekly_amount = $request->loan_amount/$request->loan_term;
            $user = User::create(['name'=>$request->name,'email'=>$request->email,'loan_amount'=>$request->loan_amount,'loan_term'=>$request->loan_term,'password'=>Hash::make($request->password),'weekly_amount'=>$weekly_amount]);
            
            if(!$user){
                DB::rollback();
                return response(['message'=>"Unable to create account, Please try again later.",'statuscode'=>$this->errorStatus,'status'=>false], $this->errorStatus);
            }

            Auth::login($user);
            Auth::user()->AauthAcessToken()->delete();
            $tokenResult = $user->createToken('Token');
            $token = $tokenResult->token;
            $token->save();
            
            $responseData = $user;
            $responseData['token_type'] = 'Bearer';
            $responseData['access_token'] = $tokenResult->accessToken;
       
            
            DB::commit();

            return response()->json([
                'status'   => true,
                'statuscode'   => $this->successStatus,
                'message'      => trans('Account created successfully'),
                'data'         =>$responseData
            ], $this->successStatus);

        }catch(\Exception $e){
            DB::rollback();
            return response()->json(['statuscode'=>$this->errorStatus, 'message'=>$e->getMessage(),'status'=>false], $this->errorStatus);
        }
    }

    /**
     * method used to validate login api params and return auth bearer token after successful loggedin.
     * **/
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'bail|required|email|exists:users,email',
            'password'=>'required'
        ]);

        if ($validator->fails()) {
            return response(['message'=>$validator->errors()->first(),'statuscode'=>$this->errorStatus,'status'=>false], $this->errorStatus);
        }

        $user = User::where('email',$request->email)->first();

        if (!$user)
        {
            return response(['message'=>"User account not found",'statuscode'=>$this->errorStatus,'status'=>false], $this->errorStatus);
        }
        elseif (!Hash::check(request('password'), $user->password))
        {
            return response(['message'=>"Wrong password",'statuscode'=>$this->errorStatus,'status'=>false], $this->errorStatus);
        }
        
        Auth::login($user);
        Auth::user()->AauthAcessToken()->delete();
        $tokenResult = $user->createToken('Token');
        $token = $tokenResult->token;
        $token->save();
        
        $responseData = $user;
        $responseData['token_type'] = 'Bearer';
        $responseData['access_token'] = $tokenResult->accessToken;
        
        
        DB::commit();

        return response()->json([
            'status'   => true,
            'statuscode'   => $this->successStatus,
            'message'      => 'Logged in successfully',
            'data'         =>$responseData
        ], $this->successStatus);
    }
}
