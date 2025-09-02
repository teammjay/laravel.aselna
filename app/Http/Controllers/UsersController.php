<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAccount;
use App\Models\UserNotificationAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return view('user.list', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('user.add',compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        User::create($request->all());

        return redirect()->route('user.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $userAccount = UserAccount::with('outgoingAccount')->where('user_id', $id)->get();
        return view('user.update', compact('user','userAccount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        User::findOrFail($id)->update($request->all());

        return redirect()->route('user.index')
            ->with('success', 'User updated successfully.');
    }

    public function saveTabData(Request $request)
    {
        $user_id = $request->input('user_id');
        $tabId = $request->input('tab');
        $request->except('user_id', 'tab');

        // Logic to handle saving data based on $tabId
        if ($tabId == 1) {

            $validator = Validator::make(request()->all(), [
                'name' => 'required',
                'email' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($user_id); // Retrieve the store by ID
            $user->update($request->all());
            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);


        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        User::findOrFail($id)->delete();

        return redirect()->route('user.index')
            ->with('success', 'User deleted successfully.');
    }

    public function saveNotificationAccount(Request $request){

        try {

            $validator = Validator::make(request()->all(), [
                'name' => 'required',
                'email' => 'required',
                'acc_out_protocol' => 'required',
                'acc_out_email' => 'required',
                'acc_out_user_id' => 'required',
                'acc_out_password' => 'required',
                'acc_out_server' => 'required',
                'acc_out_port' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            /* save outgoing account*/
            $outgoingRecords = [
                'protocol' => $request->input('acc_out_protocol'),
                'email' => $request->input('acc_out_email'),
                'user_id' => $request->input('user_id'),
                'username' => $request->input('acc_out_user_id'),
                'password' => $request->input('acc_out_password'),
                'server' => $request->input('acc_out_server'),
                'port' => $request->input('acc_out_port'),
                'requires_ssl' => $request->has('acc_out_requires_ssl') ? 1 : 0,
                'requires_tls' => $request->has('acc_out_requires_tls') ? 1 : 0,
                'requires_authentication' => $request->has('acc_out_requires_ssl') ? 1 : 0,
                'ssl_port' => $request->input('acc_out_ssl_port'),
                'tls_port' => $request->input('acc_out_tls_port'),
                'filter_from_date' => date('Y-m-d H:i:s'),
                'created_by' => auth()->user()->name ?? 'admin',
                'updated_by' => auth()->user()->name ?? 'admin',
            ];

            $messasgeOutAcc = UserNotificationAccount::updateOrCreate(
                ['id' => $request->input('acc_out_id')],
                $outgoingRecords
            );

            $outgoingAccountid = $messasgeOutAcc->id;

            if($outgoingAccountid) {
                $storeAccount = [
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'user_id' => $request->input('user_id'),
                    'outgoing_account_id' => $outgoingAccountid,
                    'api_account' => $request->has('api_account') ? 1 : 0,
                    'active' => $request->has('active') ? 1 : 0,
                    'version' => 1,
                    'created_by' => auth()->user()->name ?? 'admin',
                    'updated_by' => auth()->user()->name ?? 'admin',
                ];

                UserAccount::updateOrCreate([
                    'id' => $request->input('user_account_id'),
                    'user_id' => $request->input('user_id')
                ],
                    $storeAccount
                );

                return response()->json(['success' => true, 'message' => 'Data saved successfully']);
            }else{
                return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again']);
            }

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function removeNotificationAccount(Request $request)
    {
        // Find the message account by id
        $accId = $request->input('accId');
        $notificationAcc = UserAccount::find($accId);
        $notificationRecord = $notificationAcc->toArray();
        //print_r($messageRecord);die;
        if ($notificationAcc && !empty($notificationRecord)) {
            $notificationAcc->delete(); // Delete the store message account
            $notiOutAcc = UserAccount::find($notificationRecord['outgoing_account_id']);
            $notiOutAcc->delete(); // Delete the message account


            // Return a success response
            return response()->json(['success' => true, 'message' => 'Message account deleted successfully']);
        }

        // If the message accoubt doesn't exist, return an error
        return response()->json(['error' => 'Message account not found!'], 422);
    }

    public function getUserNotificationAccount(Request $request){

        $account_id = $request->input('account_id');
        $record = UserAccount::with('outgoingAccount')->where('id',$account_id)->first();

        return response()->json(['success' => true, 'data'=>$record, 'outgoingAccount' => $record->outgoingAccount]);exit;
    }
}
