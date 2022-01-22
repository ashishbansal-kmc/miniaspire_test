<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Validator;
use DB;
use Auth;
use App\User;
use Hash;

class DataController extends Controller
{
    public $successStatus = 200;
    public $errorValidation = 422;
    public $errorStatus = 400;


    /**
     * method used to pay weekly premium.
     * **/
    public function payment(Request $request){
        $user = Auth::user();
        
        $balance = $user->loan_amount-$user->amount_paid;
        if(!$balance){
            return response()->json(['statuscode'=>$this->errorStatus,'status'=>false,'message'=>"you have already paid your loan amounts"], $this->errorStatus);
        }
        $amount = $user->amount_paid+$user->weekly_amount;
        User::where('id', $user->id)->update(['amount_paid'=>$amount]);

        return response()->json(['status'=>true,'statuscode'=>$this->successStatus,'message'=>"weekly loan amount paid successfully"], $this->successStatus);
    }
}
