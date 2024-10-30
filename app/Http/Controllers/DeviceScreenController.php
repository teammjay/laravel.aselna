<?php

namespace App\Http\Controllers;

use App\Models\DeviceScreen;
use Illuminate\Http\Request;

class DeviceScreenController extends Controller
{
    public function index()
    {
        $device_screens = DeviceScreen::all();
        return view('device_screen.list', compact('device_screens'));
    }

    public function create()
    {
        return view('device_screen.add');
    }


    public function store(Request $request)
    {
        $request->validate([
            'size' => 'required',
            'status' => 'required|in:on,off',
        ]);

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'status' => $statusCode]);

        DeviceScreen::create($request->all());

        return redirect()->route('device_screen.index')
            ->with('success', 'Device Screen created successfully.');
    }

    public function edit($id)
    {
        $device_screen = DeviceScreen::findOrFail($id);
        return view('device_screen.add', compact('device_screen'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'size' => 'required',
            'status' => 'required|in:on,off',
        ]);

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        DeviceScreen::findOrFail($id)->update($request->all());

        return redirect()->route('device_screen.index')
            ->with('success', 'Device Screen updated successfully.');
    }

    public function destroy($id)
    {
        DeviceScreen::findOrFail($id)->delete();

        return redirect()->route('device_screen.index')
            ->with('success', 'Device Screen deleted successfully.');
    }

}
