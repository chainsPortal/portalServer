<?php

namespace App\Http\Controllers;
use App\Models\transaction;
use App\Models\transaction_validation;
use App\Models\network;
use App\Models\networkfee;
use App\Models\tokenasset;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Database\Query\Builder;
use Carbon\Carbon;
use DB;
class TransactionController extends Controller
{
    //
    public function averagedetailedTransactionProcessingTime($chain_id ,$destination){
     
    $transactions = transaction::Where( [   "chain_id" => $chain_id , "interfacing_chain_id" =>  $destination , 'status' => 'registered'] )
     ->where(function($query)   {
        $query->where('type','send')->orWhere('type','burn');
        })->with('pairTransaction')->latest()->take(2)->get();
        
    $totalTransactions = count( $transactions);
    $totalOracleTimeInSec = 60;
    $totalValidatorTimeInSec = 60;
    $averageOracleTimeInSec = 60;
    $averageValidatorTimeInSec = 60;
    foreach($transactions as $transaction){
        
        $finishTime =  Carbon::parse($transaction["completed_at"]);
        $startTime = Carbon::parse($transaction["created_at"]);
        $totalDuration = $finishTime->diffInSeconds($startTime);
        $totalOracleTimeInSec += $totalDuration;
        $pairTransaction =  $transaction['pairTransaction'];
        $vfinishTime = Carbon::parse($pairTransaction["completed_at"]);
        $vstartTime = Carbon::parse($pairTransaction["created_at"]);
        $totalDuration = $vfinishTime->diffInSeconds($vstartTime);
        $totalValidatorTimeInSec += $totalDuration;


        
    }
   
    if($totalTransactions > 0){
        $averageOracleTimeInSec = round($totalOracleTimeInSec/$totalTransactions);
        $averageValidatorTimeInSec = round($totalValidatorTimeInSec/$totalTransactions); 
    }
   
         $response['code'] = 200;
         $response['total_sample'] = $totalTransactions;
         $response['total_relaying_time'] = $totalOracleTimeInSec;
         $response['total_validating_time'] = $totalValidatorTimeInSec;
         $response['average_relaying_time'] = $averageOracleTimeInSec;
         $response['average_validating_time'] = $averageValidatorTimeInSec;
        return response()->json($response ,200);

    

    }
    public function averageTransactionProcessingTime($chain_id ,$destination){
        $transactionAvg = DB::table('transactions')
        ->select(DB::raw("AVG(TIME_TO_SEC(TIMEDIFF(updated_at, created_at))) AS timediff"))
        ->where(['type' =>  'send'  ,  "chain_id" => $chain_id , "interfacing_chain_id" =>  $destination , 'status' => 'registered'])->first();
        if(is_null($transactionAvg->timediff)){
            $response['time'] = 0;
        }else{
            $response['time'] = $transactionAvg->timediff;
        }
        $response['code'] = 200;
        return response()->json($response ,200);

    }
    public function networkStat() {
        $totalTransactions = transaction::where(function($query)  {
            $query->where('type','send')->orWhere('type','burn');
            })->count();
        $networks =  network::where('status' , 'active')->get();
        
        foreach($networks as $network){
            $networkTotalTransactions = transaction::where( 'chain_id', $network->chain_id)->where(function($query)  {
                $query->where('type','send')->orWhere('type','burn');
                })->count();
            $percentage = round($networkTotalTransactions / $totalTransactions * 100);
            $network['percentage'] = $percentage;
            $network['transactionCount'] = $networkTotalTransactions;

        }
        // $networks =  usort($networks,function($first,$second){
        //     return $first->transactionCount < $second->transactionCount;
        // });
        $response['code'] = 200;
        $response['data'] = $networks;
        return response()->json($response ,200);

    }


   
    public function assetStat($chain_id) {
      
        if($chain_id == 0){
            $totalTransactions = transaction::where(function($query)  {
                $query->where('type','send')->orWhere('type','burn');
                })->count();
            $assets =  tokenasset::where('type' , 'native')->get();
        }else{
            $totalTransactions = transaction::where( 'chain_id', $chain_id)->where(function($query)  {
                $query->where('type','send')->orWhere('type','burn');
                })->count();
            $assets =  tokenasset::where(['type' =>  'native' , "chain_id" =>$chain_id])->get();
        }
        

        
        foreach($assets as $asset){
            $assetTotalTransactions = transaction::where('asset_address', $asset->token_address)->where(function($query)  {
                $query->where('type','send')->orWhere('type','burn');
                })->count();
            $percentage = round($assetTotalTransactions / $totalTransactions * 100);
            $asset['percentage'] = $percentage;
            $asset['transactionCount'] = $assetTotalTransactions;
        }
    //    $assets =  usort($assets,function($first,$second){
    //         return $first->transactionCount < $second->transactionCount;
    //     });
        $response['code'] = 200;
        $response['data'] = $assets;
        return response()->json($response ,200);

    }
    public function validateTransaction(request $request){
        $validator = Validator::make($request->all(), [
            "transaction_id" =>  "required",
            "chain_id" =>  "required|exists:networks",
            'signer' => 'required|exists:signers',
            'verdict' => 'required',
            'signature' => 'required'
      ]);
      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
      $validTransaction = transaction::where(['transaction_id' => $request->transaction_id , 'chain_id' => $request->chain_id , 'status' => 'pending'])->first();
      if(!$validTransaction) {
        $response['code'] = 400;
        $response['error'] = "Validated Transaction";
        return response()->json($response ,200);
      }
      $signed = transaction_validation::where(['transaction_id' => $request->transaction_id , 'chain_id' => $request->chain_id , 'signer' => $request->signer])->first();
                if($signed ){
                    $response['code'] = 400;
                    $response['error'] = "already signed";
                    return response()->json($response ,200);
                }
     $sign = new transaction_validation;
     $sign->transaction_id = $request->transaction_id;
     $sign->chain_id = $request->chain_id;
     $sign->signer = $request->signer;
     $sign->verdict = $request->verdict;
     $sign->signature = $request->signature;
     $sign->save();
     $response['code'] = '200';
     return response()->json($response ,200);
    }
    public function update(){
        // $transactions = transaction::Where('created_at', '>' , Carbon::now()->subDays(2))->get();
        // foreach($transactions as $transaction){
        //   $transaction->status = 'pending';
        //   $transaction->save();
        // }
        // return count($transactions);
        
           $transactions = transaction::Where(['type' => 'send' , 'status' => 'invalid'])->get();
//           ->where(function($query)  {
// 			$query->where('type','mint')->orWhere('type','claim');
//             })
            foreach($transactions as $transaction){
                $transaction->status = 'pending';
                $transaction->type = 'burn';
                $transaction->save();
            }
          return count($transactions);
    }
    public function registerOutgoingTransaction(request $request){ 
        $validator = Validator::make($request->all(), [
            "transaction_id" =>  "required",
            "transaction_hash" =>  "required",
            "chain_id" =>  "required|exists:networks",
            'interfacing_chain_id' => 'required',
            'asset_address' => 'required',
            'asset_id' => 'required',
            'nounce' => 'required',
            'reciever' => 'required',
            'sender' => 'required',
            'send' => 'required|boolean',
      ]);
      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
      
      //   $registered = transaction::where(['transaction_hash' => $request->transaction_hash , 'chain_id' => $request->chain_id ])->first();
      $registered = transaction::where(['transaction_id' => $request->transaction_id , 'chain_id' => $request->chain_id  , 'interfacing_chain_id' => $request->interfacing_chain_id  , 'asset_address' => $request->asset_address])->first();
      if(isset($registered)){
          if($registered->transaction_hash == ''){
            $registered->transaction_hash = $request->transaction_hash;
            $registered->completed_at = $registered->updated_at;
            $registered->save();
            $response['catch'] = 'true';
          }else{
            $response['catch'] = 'false';
          }
        
        $response['code'] = '400';
        $response['error'] = 'already registerred';
        return response()->json($response ,200);
      }
      $transaction = new transaction;
      $transaction->sender = $request->sender;
      $transaction->transaction_hash  = $request->transaction_hash;
      $transaction->transaction_id = $request->transaction_id;
      $transaction->chain_id = $request->chain_id;
      $transaction->interfacing_chain_id = $request->interfacing_chain_id;
      $transaction->asset_address = $request->asset_address;
      $transaction->asset_id = $request->asset_id;
      $transaction->nounce = $request->nounce;
      $transaction->reciever = $request->reciever;
      $transaction->status = 'pending';
      if($request->send){
      $transaction->type = 'send';
      }else{
      $transaction->type = 'burn';
      }
      $transaction->save();
      $response['code'] = '200';
      return response()->json($response ,200);
    }
    public function registerIncommingTransaction(request $request){
        $validator = Validator::make($request->all(), [
            "transaction_id" =>  "required",
            "transaction_hash" =>  "required",
            "chain_id" =>  "required",
            'interfacing_chain_id' => 'required',
            'asset_address' => 'required',
            'asset_id' => 'required',
            'nounce' => 'required',
            'reciever' => 'required',
            'mint' => 'required|boolean',
      ]);
      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
      $registered = transaction::where(['transaction_id' => $request->transaction_id , 'chain_id' => $request->chain_id  , 'interfacing_chain_id' => $request->interfacing_chain_id  , 'asset_address' => $request->asset_address])->first();
      if(isset($registered)){
        $response['code'] = '400';
        $response['error'] = 'already registerred';
        return response()->json($response ,200);
      }
      $othertransaction = transaction::where(['transaction_id' => $request->transaction_id , 'chain_id' => $request->interfacing_chain_id ])->first();
      if(isset($othertransaction)){
          $othertransaction->status = 'registered';
          $othertransaction->save();
      }
      $transaction = new transaction;
      $transaction->transaction_hash  = $request->transaction_hash;
      $transaction->transaction_id = $request->transaction_id;
      $transaction->chain_id = $request->chain_id;
      $transaction->interfacing_chain_id = $request->interfacing_chain_id;
      $transaction->asset_address = $request->asset_address;
      $transaction->asset_id = $request->asset_id;
      $transaction->nounce = $request->nounce;
      $transaction->reciever = $request->reciever;
      $transaction->status = 'pending';
      if($request->mint){
      $transaction->type = 'mint';
      }else{
      $transaction->type = 'claim';
      }
      $transaction->save();
      $response['code'] = '200';
      return response()->json($response ,200);
    }
    public function getAllOutgoingTransactions(){
       
        // return "ee";->
        $response['tatal_transactions'] = transaction::where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
        })->count();
        $response['all_transactions'] = transaction::count();
        $response['tatal_pending'] = transaction::where('status' ,'pending')->count();
        $response['pending_outgoing'] =  transaction::Where( 'status' , 'pending' )->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->count();;
        $response['pending_incomming'] =  transaction::Where( 'status' , 'pending' )->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->count();
        $response['out_invalid'] = transaction::Where('status', 'invalid' )->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->count();
             $response['in_invalid'] = transaction::Where('status', 'invalid' )->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->count();
        $response['in_kcc_pending'] = transaction::Where(['chain_id' => '322' , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->count();
        $response['in_rinkeby_pending'] = transaction::Where(['chain_id' => '4' , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->count();
        $response['in_bsc_pending'] = transaction::Where(['chain_id' => '97' , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->count();
        $response['in_avax_pending'] = transaction::Where(['chain_id' => '43113' , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->count();
        $response['in_polygon_pending'] = transaction::Where(['chain_id' => '80001' , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->count();
        $response['in_fantom_pending'] = transaction::Where(['chain_id' => '4002' , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->count();
        $response['out_kcc_pending'] = transaction::Where(['chain_id' => '322', 'status' => 'pending'])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->count();
        $response['out_rinkeby_pending'] =transaction::Where(['chain_id' => '4', 'status' => 'pending'])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->count();
        $response['out_bsc_pending'] = transaction::Where(['chain_id' => '97' , 'status' => 'pending'])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->count();
        $response['out_avax_pending'] =transaction::Where(['chain_id' => '43113', 'status' => 'pending'])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->count();
        $response['out_polygon_pending'] = transaction::Where(['chain_id' =>'80001', 'status' => 'pending'])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->count();
        $response['out_fantom_pending'] = transaction::Where(['chain_id' => '4002', 'status' => 'pending'])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->count();
        
        
        $response['code'] = '200';
        return response()->json($response ,200);
    }
    public function getCompletedOutgoingTransactions($chain_id , $limit = 10 ) {
        
        if($chain_id == '0'){
            $transactions = transaction::where('status', '<>' , 'pending')->where(function($query)  {
                $query->where('type','send')->orWhere('type','burn');
                })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit); 
        }else{
            $transactions = transaction::where('status', '<>' , 'pending')->where('chain_id' , $chain_id)->where(function($query)  {
                $query->where('type','send')->orWhere('type','burn');
                })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit);
        }
        
        $response['transactions'] = $transactions;
        $response['code'] = '200';
        return response()->json($response ,200);
    }
    public function getOutgoingTransactions($chain_id , $limit = 10 ) {
        
        if($chain_id == '0'){
            $transactions = transaction::where(function($query)  {
                $query->where('type','send')->orWhere('type','burn');
                })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit); 
        }else{
            $transactions = transaction::where('chain_id' , $chain_id)->where(function($query)  {
                $query->where('type','send')->orWhere('type','burn');
                })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit);
        }
        
        $response['transactions'] = $transactions;
        $response['code'] = '200';
        return response()->json($response ,200);
    }
    public function getAllIncommingTransactions($chain_id){
         $transactions = transaction::Where('chain_id' , $chain_id)->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate(10);
       $response['transactions'] = $transactions;
        $response['code'] = '200';
        return response()->json($response ,200);
    }
    public function getIncommingTransactions($chain_id , $limit = 10){
        if($chain_id == '0'){
            $transactions = transaction::Where(function($query)  {
                $query->where('type','mint')->orWhere('type','claim');
                })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit);
        }else{
            $transactions = transaction::Where('chain_id' , $chain_id)->where(function($query)  {
                $query->where('type','mint')->orWhere('type','claim');
                })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit);
        }
       
       $response['transactions'] = $transactions;
        $response['code'] = '200';
        return response()->json($response ,200);
    }


    public function getPendingOutgoingTransactionsWithLimit($chain_id , $limit = 10){
        if($chain_id == '0'){
            $transactions = transaction::Where( 'status' , 'pending' )->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit);
        }else{
            $transactions = transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit);
        }
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
    public function getPendingOutgoingTransactions($chain_id ){
       
        $transactions = transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->get();
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
     public function getRandomPendingOutgoingTransactions($chain_id){
          $transactions = [];
        if(count(transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->get()) > 20) {
                 $transactions = transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','send')->orWhere('type','burn');
            })->get()->random(20);
            }
      
        
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);  
    }
    public function getRandomPendingIncommingTransactions($chain_id){
        $transactions = [];
        if(count(transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->get()) > 20){
                $transactions =  transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->with('validation')->get()->random(20);
            }
        
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);  
    }
    public function getRandomPendingIncommingTransactionsNotsigned($chain_id, $signer){
        $transactions = [];
        
        if(count(transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->get()) > 20){
                $transactions =  transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->get()->random(20);
            }
            foreach($transactions as $key => $transaction){
                $trx =transaction_validation::where(['transaction_id' => $transaction->transaction_id , 'chain_id' => $chain_id , 'signer' => $signer])->first();
                if(isset($trx )){
                    unset($transactions[$key]);
                }
         }
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);  
    }
    public function getPendingIncommingTransaction($chain_id){
       
          $transactions = transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
			$query->where('type','mint')->orWhere('type','claim');
            })->with('validation')->get();
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
    public function getPendingIncommingTransactionNotSigned($chain_id, $signer){
       
        $transactions = transaction::Where(['chain_id' => $chain_id  , 'status' => 'pending' ])->where(function($query)  {
          $query->where('type','mint')->orWhere('type','claim');
          })->get();
      foreach($transactions as $key => $transaction){
        $trx =transaction_validation::where(['transaction_id' => $transaction->transaction_id , 'chain_id' => $chain_id , 'signer' => $signer])->first();
        if(isset($trx )){
            unset($transactions[$key]);
        }

      }
      $response['count'] = count( $transactions);
      $response['transactions'] =  $transactions;
       $response['code'] = '200';
       return response()->json($response ,200);
  }
    public function OracleComfirmOutgoingTransaction(request $request){
        $validator = Validator::make($request->all(), [
            "id" => "required",
            'valid' => 'required|boolean',
            'gasused' => 'required'
      ]);

      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
      $transaction = transaction::findOrFail($request->id);
         if($request->valid){
          $transaction->status = 'registered';
           $networkfee =  networkfee::where('chain_id' , $transaction->interfacing_chain_id)->first();
           if(isset($networkfee) && $request->gasused != '0'){
            $networkfee->avarage_oracle = ($networkfee->avarage_oracle + $request->gasused) / 2;
            $networkfee->last_oracle = $request->gasused;
            $networkfee->save();
           }
         
          }else{
            $transaction->status = 'invalid';  
          }
          $transaction->completed_at = Carbon::now();
          $transaction->save();
          $response['code'] = '200';
         return response()->json($response ,200);
    }
    public function ValidatorComfirmIncommingTransaction(request $request){
        $validator = Validator::make($request->all(), [
            "id" => "required",
            "valid" => "required|boolean"
      ]);

      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
      
      $transaction = transaction::findOrFail($request->id);
       if($request->valid){
            $transaction->status = 'Completed';
            }else{
              $transaction->status = 'invalid';  
            }
           $networkfee =  networkfee::where('chain_id' , $transaction->chain_id)->first();
            if(isset($networkfee) && $request->gasused != '0'){
                $networkfee->avarage_validator = ($networkfee->avarage_validator + $request->gasused) / 2;
                $networkfee->last_validator = $request->gasused;
                $networkfee->save();
               }
          $transaction->completed_at = Carbon::now();
          $transaction->save();
          $response['code'] = '200';
         return response()->json($response ,200);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function searchOutgoingTransactionByAccount($chain_id ,$account, $limit = 10) {
        if($chain_id == '0'){
           $transactions = transaction::where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->
            where(function($query)  use($account) {
			$query->where('sender',$account)->orWhere('reciever',$account);
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
        }else{
            $transactions = transaction::Where('chain_id' , $chain_id  )->
         where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->
            where(function($query)  use($account) {
			$query->where('sender',$account)->orWhere('reciever',$account);
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
        }
         
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
     public function searchIncommingTransactionByAccount($chain_id ,$account ,$limit = 10) {
         if($chain_id == '0'){
             $transactions = transaction::
             where(function($query)   {
			$query->where('type','mint')->orWhere('type','claim');
            })->
            where(function($query)  use($account) {
			$query->where('sender',$account)->orWhere('reciever',$account);
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
         }
         else{
             $transactions = transaction::Where('chain_id' , $chain_id  )->
         where(function($query)   {
			$query->where('type','mint')->orWhere('type','claim');
            })->
            where(function($query)  use($account) {
			$query->where('sender',$account)->orWhere('reciever',$account);
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
           paginate($limit);
         }
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
    
    
    
    public function searchOutgoingTransactionByToken($chain_id ,$token, $limit = 10) {
        if($chain_id == '0'){
           $transactions = transaction::where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->
            where(function($query)  use($token) {
			$query->where('asset_address', $token);
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
        }else{
            $transactions = transaction::Where('chain_id' , $chain_id  )->
         where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->
            where(function($query)  use($token) {
			$query->where('asset_address',$token);
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
        }
         
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
     public function searchIncommingTransactionByToken($chain_id ,$token ,$limit = 10) {
         if($chain_id == '0'){
             $transactions = transaction::
             where(function($query)   {
			$query->where('type','mint')->orWhere('type','claim');
            })->
            where(function($query)  use($token) {
			$query->where('asset_address',$token);
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
         }
         else{
             $transactions = transaction::Where('chain_id' , $chain_id  )->
         where(function($query)   {
			$query->where('type','mint')->orWhere('type','claim');
            })->
            where(function($query)  use($token) {
			$query->where('asset_address',$token);
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
           paginate($limit);
         }
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    } 
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function searchOutgoingTransactionByNetworkPairFilter($chain_id ,$to_chain_id,$asset , $min , $max , $limit = 10) {
        
        if($asset == "ox"){
          $transactions = transaction::Where(['chain_id' =>  $chain_id , 'interfacing_chain_id' => $to_chain_id ] )->
          where(function($query)   {
        $query->where('type','send')->orWhere('type','burn');
        })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
        paginate($limit);
        }
        else {
        if( $min >= 0 && $max > 0 ){
            // return "here";
        $transactions = transaction::Where(['chain_id' =>  $chain_id , 'interfacing_chain_id' => $to_chain_id , 'asset_address' => $asset  ] )->
        where(function($query)  use($asset , $min , $max) {
        $query->where('type','send')->orWhere('type','burn');
        })->
        where("amount" , ">=" ,(int)$min)->
        where("amount" , "<=" ,(int)$max)->
        with('pairTransaction')->orderBy('created_at' ,'DESC')->
        paginate($limit);
        } else{
        $transactions = transaction::Where(['chain_id' =>  $chain_id , 'interfacing_chain_id' => $to_chain_id , 'asset_address' => $asset] )->
        where(function($query)  use($asset , $min) {
        $query->where('type','send')->orWhere('type','burn');
        })->
        where("amount" , ">=" ,(int)$min)->
        with('pairTransaction')->orderBy('created_at' ,'DESC')->
        paginate($limit);
        }
        }
    
     
    $response['count'] = count( $transactions);
    $response['transactions'] =  $transactions;
     $response['code'] = '200';
     return response()->json($response ,200);
    }
    
        public function searchOutgoingTransactionByNetworkPair($chain_id ,$to_chain_id, $limit = 10) {
        
        $transactions = transaction::Where(['chain_id' =>  $chain_id , 'interfacing_chain_id' => $to_chain_id ] )->
         where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
         
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
     public function searchIncommingTransactionByNetworkPair($chain_id ,$to_chain_id ,$limit = 10) {
           $transactions = transaction::Where(['chain_id' =>  $chain_id , 'interfacing_chain_id' => $to_chain_id ]  )->
         where(function($query)   {
			$query->where('type','mint')->orWhere('type','claim');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
           paginate($limit);
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    } 
    
    
    public function searchTransactionByID($id){
        $transactions = transaction::Where('id' ,  $id)->with('pairTransaction')->first();
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
    
    
    public function searchOutgoingTransactionByTransactionID($chain_id ,$transaction_id, $limit = 10) {
        
        if($chain_id == '0'){
           $transactions = transaction::Where( 'transaction_id' , $transaction_id  )->
         where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
        }else{
            $transactions = transaction::Where(['chain_id' =>  $chain_id , 'transaction_id' => $transaction_id ] )->
         where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
        }
         
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }
     public function searchIncommingTransactionByTransactionID($chain_id ,$transaction_id ,$limit = 10) {
          if($chain_id == '0'){
              $transactions = transaction::Where( 'transaction_id' , $transaction_id  )->
         where(function($query)   {
			$query->where('type','mint')->orWhere('type','claim');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit);
          }else{
              $transactions = transaction::Where(['chain_id' =>  $chain_id , 'transaction_id' => $transaction_id ] )->
         where(function($query){
			$query->where('type','mint')->orWhere('type','claim');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
           paginate($limit);
           //tell
          }
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    } 
    
    public function searchOutgoingTransactionByTransactionHash($chain_id ,$transaction_hash, $limit = 10) {
        
        if($chain_id == '0'){
           $transactions = transaction::Where( 'transaction_hash' , $transaction_hash  )->
         where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
        }else{
            $transactions = transaction::Where(['chain_id' =>  $chain_id , 'transaction_hash' => $transaction_hash ] )->
         where(function($query)   {
			$query->where('type','send')->orWhere('type','burn');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
            paginate($limit);
        }
         
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }

     public function searchIncommingTransactionByTransactionHash($chain_id ,$transaction_hash ,$limit = 10) {
          if($chain_id == '0'){
              $transactions = transaction::Where( 'transaction_hash' , $transaction_hash  )->
         where(function($query)   {
			$query->where('type','mint')->orWhere('type','claim');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->paginate($limit);
          }else{
              $transactions = transaction::Where(['chain_id' =>  $chain_id , 'transaction_hash' => $transaction_hash ] )->
         where(function($query){
			$query->where('type','mint')->orWhere('type','claim');
            })->with('pairTransaction')->orderBy('created_at' ,'DESC')->
           paginate($limit);
           //tell
          }
        $response['count'] = count( $transactions);
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    } 

    public function searchTransactionByTransactionHash($transaction_hash){
        $transactions = transaction::Where('transaction_hash' ,$transaction_hash  )->with('pairTransaction')->first();
        $response['transactions'] =  $transactions;
         $response['code'] = '200';
         return response()->json($response ,200);
    }

}