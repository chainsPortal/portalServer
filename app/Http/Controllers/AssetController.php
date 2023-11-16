<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\network;
use App\Models\extradata;
use App\Models\block_scanner;
use App\Models\asset;
use App\Models\asset_supported_chain;
use App\Models\pool;
use App\Models\farm;
use App\Models\transaction;
use App\Models\networkfee;
use Validator;
use DB;
class AssetController extends Controller
{
 
   
     
    public function getTokensUnderManagement($address , $chain_id) {
      if($chain_id == 0 ){
        $assets = asset::where(['manager' => $address  ])->with('supportedChains')->paginate(10);
  
      }  else{
        $assets = asset::where(['manager' => $address , 'chain_id' => $chain_id ])->with('supportedChains')->paginate(10);
        
      }   
      $response['code'] = 200;
        $response['data'] =  $assets;
        return response()->json($response ,200);  
      }
    public function getUserDeployedToken($address , $chain_id) {
    if($chain_id == 0 ){
      $assets = asset::where(['deployer' => $address , 'type' => 'native'])->with('supportedChains')->paginate(10);

    }  else{
      $assets = asset::where(['deployer' => $address , 'chain_id' => $chain_id , 'type' => 'native'])->with('supportedChains')->paginate(10);
      
    }  
    $response['code'] = 200;
      $response['data'] =  $assets;
      return response()->json($response ,200);   
    }

    public function updateAssetImage(request $request) {
      $validator = Validator::make($request->all(), [
        'chain_id' => 'required|exists:networks',
        'asset_address' => 'required',
        'logo' => 'required'
  ]);
  if ($validator->fails()) {
       
        $response['code'] = 400;
        $response['error'] = $validator->messages()->all();
        return response()->json($response ,200);
  } 
  $token = asset::where(['asset_address' => $request->asset_address , 'chain_id' => $request->chain_id , 'type' => "native"])->first();
    if(isset($token)){
      $token->logo = $request->logo;
      $token->save();

      $foriegnAssets = asset::where(["chain_from_asset_address" => $request->asset_address , "chain_from_id" => $request->chain_id])->get();
      if(count($foriegnAssets) > 0){
        foreach($foriegnAssets as $asset){
          $asset->logo = $request->logo;
          $asset->save();
        }
      }
      $response['code'] = 200;
       return response()->json($response ,200); 
    }  
    $response['code'] = 404;
       $response['error'] =  "invalid asset";
       return response()->json($response ,200); 
    }
    public function  getnetworkfees(){
      $networkFees = networkfee::all();
      $response['code'] = 200;
      $response['data'] =  $networkFees;
      return response()->json($response ,200); 
   }

  
    public function updateAsset(request $request){
      
    $validator = Validator::make($request->all(), [
      'chain_id' => 'required|exists:networks',
      'asset_address' => 'required',
      'min' => 'required',
      'max' => 'required',
      'manager' => 'required',
      'feeRemitance' => 'required',
      'type' => 'required'
        ]);
        if ($validator->fails()) {

            
              $response['code'] = 400;
              $response['error'] = $validator->messages()->all();
              return response()->json($response ,200);
        }
        $asset = asset::where(['asset_address' => $request->asset_address , 'chain_id' => $request->chain_id , 'type' => $request->type])->first();
        if($asset){
          $asset->min_amount = $request->min;
          $asset->max_amount = $request->max;
          $asset->manager = $request->manager;
          $asset->feeRemitance = $request->feeRemitance;
          $asset->save();
          $response['code'] = 200;
        $response['message'] = "asset added successfully";
        return response()->json($response ,200);
        }
        else{
          $response['code'] = 404;
        $response['error'] = "asset not found";
        return response()->json($response ,200);
        }

    }
    public function registerNativeAsset(request $request){
      $validator = Validator::make($request->all(), [
          'chain_id' => 'required|exists:networks',
          'asset_address' => 'required',
          'name' => 'required',
          'symbol' => 'required',
          'deployer' => 'required',
          'manager' => 'required',
          'feeRemitance' => 'required',
          'supported_chains.*' => "required|distinct|min:1",
          'destination_addresses.*' => "required|min:1"
    ]);
    if ($validator->fails()) {

         
          $response['code'] = 400;
          $response['error'] = $validator->messages()->all();
          return response()->json($response ,200);
    }
    if(count($request->supported_chains) != count($request->destination_addresses)){
      $response['code'] = 400;
      $response['error'] = "chains and addressess mismatch";
      return response()->json($response ,200); 
      }
      $exists = asset::where(['asset_address' => $request->asset_address , 'chain_id' => $request->chain_id , 'type' => "native"])->first();
      if($exists){
          $exists->status ='pending';
          if ($request->logo) {
            $exists->logo = $request->logo;
          }
          $exists->save();   
      }else{
          $token = new asset;

      $token->chain_id = $request->chain_id;
      $token->type = 'native';
      $token->asset_address = $request->asset_address;
      $token->name = $request->name;
      $token->symbol = $request->symbol;
      $token->chain_from_id = '0';
      $token->chain_from_asset_address = '0'; 
      $token->deployer = $request->deployer;
      $token->manager = $request->manager;
      $token->feeRemitance = $request->feeRemitance;
      $token->status = 'pending'; 
    

      if ($request->logo) {
          $token->logo = $request->logo;
        }
      $token->save();
      }
      
      foreach($request->supported_chains as $key => $id ){
          $registered  = asset_supported_chain::where(['chain_id' => $request->chain_id  , 'asset_address' => $request->asset_address , 'supported_chain_id' => $id] )->first();
          if(!$registered){
      
          
          $supportedChain = new asset_supported_chain;
          $supportedChain->asset_address = $request->asset_address;
          $supportedChain->chain_id = $request->chain_id;
          $supportedChain->supported_chain_id = $id;
          $supportedChain->destination_address = $request->destination_addresses[$key];
          $supportedChain->supported_chain_id = $id;
          $supportedChain->status = 'active';
          $supportedChain->save();
      }
      }
      $response['code'] = 200;
      $response['message'] = "asset added successfully";
      return response()->json($response ,200);
  }
    public function registerForiegnAsset(request $request){
        $validator = Validator::make($request->all(), [
            'chain_id' => 'required|exists:networks',
            'asset_address' => 'required',
            'name' => 'required',
            'symbol' => 'required',
            'chain_from_id' => 'required|exists:networks,chain_id',
            'chain_from_asset_address' => 'required'
      ]);
      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
        if($request->asset_address == '0x0000000000000000000000000000000000000000'){
          $response['code'] = 400;
            $response['error'] = "invalid wrapped Address";
            return response()->json($response ,200);
        }
        $nativeAsset = asset::where(['asset_address' => $request->chain_from_asset_address , 'chain_id' =>  $request->chain_from_id])->first();
        if(isset($nativeAsset)){
            $token = new asset;
           $exists =  asset::where(['type' => 'foriegn', 'asset_address' => $request->asset_address , 'chain_id' =>  $request->chain_id , 'chain_from_asset_address' => $request->chain_from_asset_address , 'chain_from_id' =>  $request->chain_from_id])->first();
          if(isset($exists)){
            $response['code'] = 400;
            $response['error'] = ['token already registered'];
            return response()->json($response ,200);  
          }else{
        $token->logo = $nativeAsset->logo;
         $token->chain_id = $request->chain_id;
        $token->type = 'foriegn';
        $token->asset_address = $request->asset_address;
        $token->name = $request->name;
        $token->symbol = $request->symbol;
        $token->manager = $nativeAsset->manager;
        $token->feeRemitance = $nativeAsset->feeRemitance;
        $token->deployer = $nativeAsset->deployer;
        $token->chain_from_id = $request->chain_from_id;
        $token->chain_from_asset_address = $request->chain_from_asset_address;
        $token->status = 'active'; 
        $token->save(); 
        $response['code'] = 200;
        $response['message'] = "asset added successfully";
        return response()->json($response ,200);
          }
         
        }else {
            $response['code'] = 404;
            $response['error'] = "not found";
            return response()->json($response ,200);   
        }
        
    }
    public function activateAsset(request $request){
       
        $validator = Validator::make($request->all(), [
            'chain_id' => 'required|exists:networks',
            'asset_address' => 'required'
      ]);

      if ($validator->fails()) {
            $res['errors'] =$validator->messages()->all();
            $res['code'] = '404';
            return response()->json($res ,200);
      }
     
      $asset =  asset::where(['asset_address' => $request->asset_address , 'chain_id' =>  $request->chain_id , 'type' => 'native'])->first();
      if(isset($asset)){
        $asset->status = 'active';  
        $asset->save();
        $response['code'] = 200;
        $response['message'] = "asset added successfully";
        return response()->json($response ,200);
       }else {
        $response['errors'] =["asset not found"];
        $response['code'] = '404';
        return response()->json($response ,200);
       }

    }
    public function getAssetSupportedChain(request $request){
       
        $validator = Validator::make($request->all(), [
            'chain_id' => 'required|exists:networks',
            'asset_address' => 'required'
      ]);

      if ($validator->fails()) {
            $res['errors'] =$validator->messages()->all();
            $res['code'] = '404';
            return response()->json($res ,200);
      }
      $assetSupportedChains = asset_supported_chain::where(['asset_address' => $request->asset_address , 'chain_id' =>  $request->chain_id , 'status' => 'active'])->paginate(10);
      $response['code'] = 200;
      $response['supported_chains'] = $assetSupportedChains;
      return response()->json($response ,200);
    }
    public function getPendingNativeAssets($chain_id) {
        $assets = asset::where(['chain_id' => $chain_id ,  'type' => 'native'  , 'status' => 'pending'])->with('supportedChains')->get();
        $response['code'] = 200;
        $response['native_assets'] = $assets;
        return response()->json($response ,200);
    }
    public function getActiveNativeAssets($chain_id ,$search = ''){
        $assets = asset::where(['chain_id' => $chain_id , 'type' => 'native' , 'status' => 'active'])->
        where(function($query)  use($search) {
			$query->where('name', 'like', '%' . $search . '%')->orWhere('symbol', 'like', '%' . $search . '%');
            })->with('supportedChains')->paginate(10);
        $response['code'] = 200;
        $response['native_assets'] = $assets;
        return response()->json($response ,200);
    }
    public function getActiveAssets($chain_id ,$search = ''){
      $assets = asset::where(['chain_id' => $chain_id  , 'status' => 'active'])->
      where(function($query)  use($search) {
    $query->where('name', 'like', '%' . $search . '%')->orWhere('symbol', 'like', '%' . $search . '%');
          })->with('supportedChains')->orderBy('type' , 'desc')->paginate(10);
      $response['code'] = 200;
      $response['assets'] = $assets;
      return response()->json($response ,200);
  }

    public function getNativeAssets($chain_id ,$search = ''){
        $assets = asset::where(['chain_id' => $chain_id , 'type' => 'native' ])->
        where(function($query)  use($search) {
			$query->where('name', 'like', '%' . $search . '%')->orWhere('symbol', 'like', '%' . $search . '%');
            })->with('supportedChains')->paginate(10);
        $response['code'] = 200;
        $response['native_assets'] = $assets;
        return response()->json($response ,200);
    }
    public function getActiveForiegnAssets($chain_id ,$search = ''){
        $assets = asset::where(['chain_id' => $chain_id ,'type' => 'foriegn'  , 'status' => 'active'])->
        where(function($query)  use($search) {
			$query->where('name', 'like', '%' . $search . '%')->orWhere('symbol', 'like', '%' . $search . '%');
            })->paginate(10);
        $response['code'] = 200;
        $response['foriegn_assets'] = $assets;
        return response()->json($response ,200);
    }
    public function getForiegnAssets($chain_id , $search = ''){
        $assets = asset::where(['chain_id' => $chain_id , 'type' => 'foriegn' ])->
        where(function($query)  use($search) {
			$query->where('name', 'like', '%' . $search . '%')->orWhere('symbol', 'like', '%' . $search . '%');
            })
        ->paginate(10);
        $response['code'] = 200;
        $response['foriegn_assets'] = $assets;
        return response()->json($response ,200);
    }
    public function getAsset($chain_id , $assetAddress){
        $asset = asset::where(['chain_id' => $chain_id , 'asset_address' => $assetAddress])->with('supportedChains')->first();
        $response['code'] = 200;
        $response['asset'] = $asset ;
        return response()->json($response ,200);
    }
    
}
