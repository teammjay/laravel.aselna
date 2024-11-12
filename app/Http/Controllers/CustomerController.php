<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
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
            Customer::create($request->all());
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
            $customer->update($request->all());
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
            // delete the customer
            $customer->delete();
            // redirect to the customers list with success message
            return redirect()->route('customers.list')->with('success', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while deleting the customer. Please try again.']);
        }
    }
}
