<?php

namespace App\Http\Controllers;

use App\Models\DeviceTypes;
use Illuminate\Http\Request;

class DeviceTypesController extends Controller
{
    // create all the controllers for this model
    public function index()
    {
        // get the data in the pagination format for datatable to display
        $deviceTypes = DeviceTypes::select('id', 'name', 'notes1')
            ->get();
        return view('device_types.list', compact('deviceTypes'));
    }

    public function create()
    {
        return view('device_types.add');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:30',
            'notes1' => 'nullable|string|max:255'
        ]);

        try {
            // save the data to the database
            DeviceTypes::create($request->all());
            // redirect to the device types list with success message
            return redirect()->route('device_types.list')->with('success', 'Device type created successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the device type. Please try again.']);
        }
    }

    public function edit($id)
    {
        $deviceType = DeviceTypes::findOrFail($id);
        return view('device_types.add', compact('deviceType'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:30',
            'notes1' => 'nullable|string|max:255',
        ]);

        try {
            // save the data to the database
            DeviceTypes::findOrFail($id)->update($request->all());
            // redirect to the device types list with success message
            return redirect()->route('device_types.list')->with('success', 'Device type updated successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while updating the device type. Please try again.']);
        }
    }

    public function destroy($id)
    {
        // delete the device type
        DeviceTypes::findOrFail($id)->delete();
        // redirect to the device types list with success message
        return redirect()->route('device_types.list')->with('success', 'Device type deleted successfully.');
    }
}
