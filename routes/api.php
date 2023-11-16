<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthenticationController::class, 'register']);
Route::post('/login', [AuthenticationController::class, 'login']);

Route::get('/networks', [NetworkController::class, 'index']);

Route::get('/adminAuthError', [AuthenticationController::class, 'adminAuthError']);
Route::middleware( ['auth:api', 'admin'])->group(function () {
    Route::post('/network/add', [NetworkController::class, 'addNetwork']);
    Route::post('/network/update', [networkController::class, 'updateNetwork']);
    Route::post('/network/activate', [networkController::class, 'flipNetworkState']);

    // Asset route
    Route::post('/asset/register_native', [AssetController::class, 'registerNativeAsset']);
    Route::post('/asset/register_foriegn', [assetController::class, 'registerForiegnAsset']);
    Route::post('/asset/activate_asset', [assetController::class, 'activateAsset']);    

    // Transaction Route
    Route::post('/transaction/register_outgoing_transaction', [TransactionController::class, 'registerOutgoingTransaction']);
    Route::post('/transaction/register_incomming_transaction', [TransactionController::class, 'registerIncommingTransaction']);
    Route::post('/transaction/oracle_confirm_transaction', [TransactionController::class, 'OracleComfirmOutgoingTransaction']);
    Route::post('/transaction/validator_confirm_transaction', [TransactionController::class, 'ValidatorComfirmIncommingTransaction']);
    Route::post('/transaction/validate_transaction', [transactionController::class, 'validateTransaction']);
    Route::post('/transaction/addsigner', [authenticationController::class, 'addSigner']);
    Route::post('/transaction/removesigner', [authenticationController::class, 'removeSigner']);

    
});

Route::post('/asset/supported_chains', [assetController::class, 'getAssetSupportedChain']);

Route::get('/asset/active_native_asset/{chain_id}/{search?}', [assetController::class, 'getActiveNativeAssets']);
Route::get('/asset/active_asset/{chain_id}/{search?}', [assetController::class, 'getActiveAssets']);
Route::get('/asset/native_asset/{chain_id}/{search?}', [assetController::class, 'getNativeAssets']);
Route::get('/asset/active_foriegn_asset/{chain_id}/{search?}', [assetController::class, 'getActiveForiegnAssets']);
Route::get('/asset/foriegn_asset/{chain_id}/{search?}', [assetController::class, 'getForiegnAssets']);
Route::get('/asset/get_pending_asset_native_assets/{chain_id}', [assetController::class, 'getPendingNativeAssets']);
Route::get('/asset/getasset/{chain_id}/{assetAddress}', [assetController::class, 'getAsset']);



Route::get('/transaction/get_outgoing_transaction/{chain_id}/{limit?}', [TransactionController::class, 'getOutgoingTransactions']);
Route::get('/transaction/get__completed_outgoing_transaction/{chain_id}/{limit?}', [TransactionController::class, 'getCompletedOutgoingTransactions']);
Route::get('/transaction/get_incomming_transaction/{chain_id}/{limit?}', [TransactionController::class, 'getIncommingTransactions']);
Route::get('/transaction/get_outgoing_transaction', [TransactionController::class, 'getAllOutgoingTransactions']);
Route::get('/transaction/get_incomming_transaction/{chain_id}', [TransactionController::class, 'getAllIncommingTransactions']);
Route::get('/transaction/get_pending_outgoing_transaction/{chain_id}', [TransactionController::class, 'getPendingOutgoingTransactions']);
Route::get('/transaction/getPendingOutgoingTransactions_with_imit/{chain_id}/{limit?}', [TransactionController::class, 'getPendingOutgoingTransactionsWithLimit']);

Route::get('/transaction/get_random_pending_incomming_transaction/{chain_id}', [TransactionController::class, 'getRandomPendingIncommingTransactions']);
Route::get('/transaction/get_random_pending_outgoing_transaction/{chain_id}', [TransactionController::class, 'getRandomPendingOutgoingTransactions']);
Route::get('/transaction/get_pending_incomming_transaction/{chain_id}', [TransactionController::class, 'getPendingIncommingTransaction']);


Route::get('/transaction/get_pending_incomming_transaction_notsigned/{chain_id}/{signer}', [TransactionController::class, 'getPendingIncommingTransactionNotSigned']);
Route::get('/transaction/get_random_pending_incomming_transaction_notsigned/{chain_id}/{signer}', [TransactionController::class, 'getRandomPendingIncommingTransactionsNotsigned']);


//transaction search searchTransactionController
Route::get('/transaction/search_outgoing_transaction_byaccount/{chain_id}/{account}/{limit?}', [TransactionController::class, 'searchOutgoingTransactionByAccount']);
Route::get('/transaction/search_incoming_transaction_byaccount/{chain_id}/{account}/{limit?}', [TransactionController::class, 'searchIncommingTransactionByAccount']);

Route::get('/transaction/search_outgoing_transaction_bytoken/{chain_id}/{account}/{limit?}', [TransactionController::class, 'searchOutgoingTransactionByToken']);
Route::get('/transaction/search_incoming_transaction_bytoken/{chain_id}/{account}/{limit?}', [TransactionController::class, 'searchIncommingTransactionByToken']);

Route::get('/transaction/search_outgoing_transaction_networkpair/{chain_id}/{to_chain_id}/{limit?}', [TransactionController::class, 'searchOutgoingTransactionByNetworkPair']);
Route::get('/transaction/search_outgoing_transaction_networkpair_filter/{chain_id}/{to_chain_id}/{asset}/{min}/{max}/{limit?}', [TransactionController::class, 'searchOutgoingTransactionByNetworkPairFilter']);

Route::get('/transaction/search_incoming_transaction_networkpair/{chain_id}/{to_chain_id}/{limit?}', [TransactionController::class, 'searchIncommingTransactionByNetworkPair']);

Route::get('/transaction/search_transaction_by_id/{id}', [TransactionController::class, 'searchTransactionByID']);


Route::get('/transaction/search_outgoing_transaction_byid/{chain_id}/{transaction_id}/{limit?}', [TransactionController::class, 'searchOutgoingTransactionByTransactionID']);
Route::get('/transaction/search_incoming_transaction_byid/{chain_id}/{transaction_id}/{limit?}', [TransactionController::class, 'searchIncommingTransactionByTransactionID']);


Route::get('/transaction/search_outgoing_transaction_byhash/{chain_id}/{transaction_hash}/{limit?}', [TransactionController::class, 'searchOutgoingTransactionByTransactionHash']);
Route::get('/transaction/search_incoming_transaction_byhash/{chain_id}/{transaction_hash}/{limit?}', [TransactionController::class, 'searchIncommingTransactionByTransactionHash']);

Route::get('/transaction/search_transaction_byhash/{transaction_hash}', [TransactionController::class, 'searchTransactionByTransactionHash']);

