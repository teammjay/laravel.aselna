<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceSubTypeRequest;
use App\Models\DeviceSubTypes;
use App\Models\DeviceTypes;
use Illuminate\Http\Request;

class DeviceSubTypesController extends Controller
{
    // create from model
    public function index()
    {
        // get the data in the pagination format for datatable to display
        $deviceSubTypes = DeviceSubTypes::all();
        return view('device_sub_types.list', compact('deviceSubTypes'));
    }

    public function create()
    {
        // get device types data for selecting the device type
        $deviceTypes = DeviceTypes::all();
        return view('device_sub_types.add', compact('deviceTypes'));
    }

    public function store(DeviceSubTypeRequest $request)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'status' => $statusCode]);

        try {
            // save the data to the database
            DeviceSubTypes::create($request->all());
            // redirect to the device sub types list with success message
            return redirect()->route('device_sub_types.list')->with('success', 'Device sub type created successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the device sub type. Please try again.']);
        }
    }

    public function edit($id)
    {
        $deviceSubType = DeviceSubTypes::findOrFail($id);
        $deviceTypes = DeviceTypes::all();
        return view('device_sub_types.add', compact('deviceSubType','deviceTypes'));
    }

    public function update(DeviceSubTypeRequest $request, $id)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        try {
            // save the data to the database
            DeviceSubTypes::findOrFail($id)->update($request->all());
            // redirect to the device sub types list with success message
            return redirect()->route('device_sub_types.list')->with('success', 'Device sub type updated successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while updating the device sub type. Please try again.']);
        }
    }

    public function destroy($id)
    {
        try {
            // delete the record from the database
            DeviceSubTypes::findOrFail($id)->delete();
            // redirect to the device sub types list with success message
            return redirect()->route('device_sub_types.list')->with('success', 'Device sub type deleted successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while deleting the device sub type. Please try again.']);
        }
    }
}
