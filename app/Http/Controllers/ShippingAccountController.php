<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingAccountRequest;
use App\Models\ShippingAccount;
use App\Models\ShippingAccountPurchase;
use App\Models\ShippingSettingCarrierAccount;
use App\Models\ShippingSolution;
use App\Utils\EndicialabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShippingAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shipping_accounts = ShippingAccount::with('shippingSolution')->get();
        return view('shipping_account.list', compact('shipping_accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shipping_solutions = ShippingSolution::all();
        return view('shipping_account.add', compact('shipping_solutions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShippingAccountRequest $request)
    {

        $request['sandbox_account'] = $request->has('sandbox_account') ? 1 : 0;
        $request['auto_recharge'] = $request->has('auto_recharge') ? 1 : 0;

        $validator = $request->validated();

        ShippingAccount::create($request->all());

        return redirect()->route('shipping_account.index')
            ->with('success', 'Shipping account created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shipping_account = ShippingAccount::findOrFail($id);
        $shipping_solutions = ShippingSolution::all();
        return view('shipping_account.add', compact('shipping_account','shipping_solutions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShippingAccountRequest $request, string $id)
    {
        $request['sandbox_account'] = $request->has('sandbox_account') ? 1 : 0;
        $request['auto_recharge'] = $request->has('auto_recharge') ? 1 : 0;

        $validator = $request->validated();

        $request['shipment_estimate_available'] = $request->has('shipment_estimate_available') ? 1 : 0;

        ShippingAccount::findOrFail($id)->update($request->all());

        return redirect()->route('shipping_account.index')
            ->with('success', 'Shipping account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        ShippingAccount::findOrFail($id)->delete();

        return redirect()->route('shipping_account.index')
            ->with('success', 'Shipping account deleted successfully.');
    }

    public function getAccountCredit(Request $request){
        $account_id = $request->input('account_id');
        if ($account_id) {
            $shippingAccount = ShippingAccount::with('shippingSolution')->where('id', $account_id)->first();
            /*echo '<pre>';
            print_r($shippingAccount);
            echo '</pre>';die;*/

            $accDetail = [
                'accountId' => $shippingAccount->account_id,
                'passPhrase' => $shippingAccount->pass_phrase,
                'requesterId' => $shippingAccount->requester_id,
                'requestId' => (string)Str::uuid()
            ];


            $endiciaClient = new EndicialabelService();
            $response = $endiciaClient->getAccountStatus($accDetail);

            $message = $postageBalance = '';
            $purchaseCredit = 0;
            if (!empty($response)) {

                if (isset($response['status']) && $response['status'] == 0) {
                    if(isset($response['postageBalance'])){
                        $postageBalance = $response['postageBalance'];

                        $purchaseCredit = 500 - $postageBalance;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'balance' => $postageBalance,
                'credit' => (int) $purchaseCredit
            ]);

        }

        return response()->json([
            'success' => false,
            'message' => 'Shipping account not found.'
        ]);
    }

    public function purchaseCredit(Request $request)
    {
        $account_id = $request->input('account_id');
        $credit = $request->input('credit_amount');
        $purchase_date = $request->input('purchase_date');
        if ($account_id) {
            $shippingAccount = ShippingAccount::with('shippingSolution')->where('id', $account_id)->first();

            $creditDetail = [
                'accountId' => $shippingAccount->account_id,
                'passPhrase' => $shippingAccount->pass_phrase,
                'requesterId' => $shippingAccount->requester_id,
                'requestId' => (string) Str::uuid(),
                'credit' => $credit,
            ];

            $endiciaClient = new EndicialabelService();
            $response = $endiciaClient->purchaseCredit($creditDetail);

            Log::info('Endicia Label Refund Response:'. json_encode($response));

            $message = 'Credit added successfully!';

            if (isset($response['status']) && $response['status'] == 0) {

                $purchaseData = [
                    'shipping_account_id' => $account_id,
                    'purchase_date' => date('Y-m-d H:i:s', strtotime($purchase_date)),
                    'amount' => $credit,
                    'errors' => NULL,
                ];
            }else {
                $purchaseData = [
                    'shipping_account_id' => $account_id,
                    'purchase_date' => date('Y-m-d H:i:s', strtotime($purchase_date)),
                    'amount' => $credit,
                    'errors' => (isset($response['message']) && $response['message'] != '') ? $response['message'] : NULL,
                ];

            }

            ShippingAccountPurchase::create($purchaseData);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        }
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong with API. Please try again later.'
        ]);
    }
}
