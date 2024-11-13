<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::all();
        return view('customers.list', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerRequest $request)
    {
        $request->validated();

        try {
            // save the data to the database
            DB::transaction(function () use ($request) {
                $customer = Customer::create($request->only('first_name','last_name','full_name','user_id'));

                // email[] and email_primary[] are optional fields but i want to save them to the database in table customer_emails using CustomerEmail Model
                // meaning email[0] and email_primary[0] are related, email[1] and email_primary[1] are related, and so on
                if ($request->has('email') && count($request->email) > 0) {
                    foreach ($request->email as $key => $email) {
                        Log::info("Email: $email");
                        // check whether this email with customer is already created then continue
                        if (!isset($email) || $customer->emails()->where('email_address', $email)->exists()) {
                            continue;
                        }
                        // if not, create it
                        $isPrimary = isset($request->email_primary[$key]) && $request->email_primary[$key] == 'on';
                        $customer->emails()->create([
                            'email_address' => $email,
                            'is_primary' => $isPrimary
                        ]);
                    }
                }

                // same with phone[] and phone_primary[]
                if ($request->has('phone') && count($request->phone) > 0) {
                    foreach ($request->phone as $key => $phone) {
                        if (!isset($phone) || $customer->phones()->where('phone_no', $phone)->exists()) {
                            continue;
                        }
                        $isPrimary = isset($request->phone_primary[$key]) && $request->phone_primary[$key] == 'on';
                        $customer->phones()->create([
                            'phone_no' => $phone,
                            'is_primary' => $isPrimary
                        ]);
                    }
                }
            });

            // redirect to the customers list with success message
            return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the customer. Please try again.']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->load('emails', 'phones', 'addresses');
        return view('customers.add', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerRequest $request, $id)
    {
        $request->validated();

        $customer = Customer::findOrFail($id);

        try {
            // save the data to the database
            DB::transaction(function () use ($request, $customer) {
                $customer->update($request->only('first_name','last_name','full_name','user_id'));


                if ($request->has('email') && count($request->email) > 0) {
                    foreach ($request->email as $key => $email) {
                        // check whether this email with customer is already created then continue
                        if (!isset($email) ||  $customer->emails()->where('email_address', $email)->exists()) {
                            continue;
                        }
                        // if not, create it
                        $isPrimary = isset($request->email_primary[$key]) && $request->email_primary[$key] == 'on';
                        $customer->emails()->create([
                            'email_address' => $email,
                            'is_primary' => $isPrimary
                        ]);
                    }
                }

                // same with phone[] and phone_primary[]
                if ($request->has('phone') && count($request->phone) > 0) {
                    foreach ($request->phone as $key => $phone) {
                        if (!isset($phone) || $customer->phones()->where('phone_no', $phone)->exists()) {
                            continue;
                        }
                        $isPrimary = isset($request->phone_primary[$key]) && $request->phone_primary[$key] == 'on';
                        $customer->phones()->create([
                            'phone_no' => $phone,
                            'is_primary' => $isPrimary
                        ]);
                    }
                }
            });

            // redirect to the customers list with success message
            return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'An error occurred while updating the customer. Please try again.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);

            DB::transaction(function () use ($customer) {
                $customer->emails()->delete();
                $customer->phones()->delete();
                $customer->addresses()->delete();
                $customer->delete();
            });

            // redirect to the customers list with success message
            return redirect()->route('customers.list')->with('success', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while deleting the customer. Please try again.']);
        }
    }
}
