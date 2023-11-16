<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\networkfee;
use App\Models\network;
use App\Models\network_supported_chain;
use Validator;
class NetworkController extends Controller
{
    //
    public function index(){
        $networks =  network::where('status' , 'active')->with('supportedChains')->get();
        $response['networks'] = $networks;
        $response['code'] = '200';
        return response()->json($response ,200);
    }

    public function addNetwork(request $request){
        $validator = Validator::make($request->all(), [
            "name" =>  "required",
            "symbol" =>  "required",
            "bridge_address" =>  "required",
            "settings_address" =>  "required",
            "controller_address" =>  "required",
            "deployer_address" =>  "required",
            "registry_address" =>  "required",
            "feeController_address" =>  "required",
            "rpc" =>  "required",
            "explorer" =>  "required",
            'chain_id' => 'required|unique:networks',
            'supported_chains.*' => "required|distinct|min:1"
      ]);

      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }

      $network = new network;
      if($request->logo){
        $network->logo = $request->logo; 
      }
      $network->name = $request->name;
      $network->symbol = $request->symbol;
      $network->settings_address = $request->settings_address;
      $network->controller_address = $request->controller_address;
      $network->bridge_address = $request->bridge_address;
      $network->deployer_address = $request->deployer_address;
      $network->registry_address = $request->registry_address;
      $network->feeController_address = $request->feeController_address;
      $network->rpc = $request->rpc;
      $network->explorer = $request->explorer;
      $network->chain_id = $request->chain_id;
      $network->status = "active";
      $network->save();
    
      foreach($request->supported_chains as $id ){
        $supportedChain = new network_supported_chain;
        $supportedChain->chain_id = $request->chain_id;
        $supportedChain->supported_chain_id = $id;
        $supportedChain->status = 'active';
        $supportedChain->save();
    }
      $response['code'] = 200;
      $response['message'] ="Network Added Successfully";
      return response()->json($response ,200);
    }
    public function updateNetwork(request $request){
        $validator = Validator::make($request->all(), [
            "name" =>  "required",
            "symbol" =>  "required",
            "bridge_address" =>  "required",
            "settings_address" =>  "required",
            "controller_address" =>  "required",
            'chain_id' => 'required|exists:networks'
            
      ]);

      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
      $network  = network::where("chain_id" , $request->chain_id)->first();
      if(isset($network)){
        $network->name = $request->name;
        $network->symbol = $request->symbol;
        $network->settings_address = $request->settings_address;
        $network->controller_address = $request->controller_address;
        $network->bridge_address = $request->bridge_address;
        $network->save();
        $response['code'] = 200;
        $response['message'] ="Network Updated Successfully";
        return response()->json($response ,200);
      }else{
        $response['code'] = 404;
        $response['error'] = ["chain Id not found"];
        return response()->json($response ,200);
      }
    }
    public function flipNetworkState(request $request){
        $validator = Validator::make($request->all(), [
            "status" =>  "required|boolean",
            'chain_id' => 'required|exists:networks'
            
      ]);

      if ($validator->fails()) {
            $response['code'] = 400;
            $response['error'] = $validator->messages()->all();
            return response()->json($response ,200);
      }
      $network  = network::where("chain_id" , $request->chain_id)->first();
      if(isset($network)){
        if($request->status){
            $network->status = 'active';

        }else{
            $network->status = 'inactive'; 
        }
     
        $network->save();
        $response['code'] = 200;
        $response['message'] ="Network Updated Successfully";
        return response()->json($response ,200);
      }else{
        $response['code'] = 404;
        $response['error'] = ["chain Id not found"];
        return response()->json($response ,200);
      }
      

    }
}
