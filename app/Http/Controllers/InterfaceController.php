<?php

namespace App\Http\Controllers;

use App\Http\Requests\InterfaceRequest;
use App\Models\Interfaces;
use App\Models\ProductType;
use Illuminate\Http\Request;

class InterfaceController extends Controller
{
    public function index()
    {
        // get the data in the pagination format for datatable to display
        $interfaces = Interfaces::with('ProductType')->get();
        return view('interface.list', compact('interfaces'));
    }

    public function create()
    {
        $productTypes = ProductType::all();
        return view('interface.add',compact('productTypes'));
    }

    public function store(InterfaceRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by]);

        try {
            // save the data to the database
            Interfaces::create($request->all());
            // redirect to the interfaces list with success message
            return redirect()->route('interface.list')->with('success', 'interface created successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the interface. Please try again.']);
        }
    }

    public function edit($id)
    {
        $interface = Interfaces::findOrFail($id);
        $productTypes = ProductType::all();
        return view('interface.add', compact('interface','productTypes'));
    }

    public function update(InterfaceRequest $request, $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        // save the data to the database
        Interfaces::findOrFail($id)->update($request->all());

        // redirect to the interfaces list
        return redirect()->route('interface.list');
    }

    public function destroy($id)
    {
        // delete the interface
        Interfaces::findOrFail($id)->delete();

        // redirect to the interfaces list
        return redirect()->route('interface.list');
    }
}
