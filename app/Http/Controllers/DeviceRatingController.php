<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceRequest;
use App\Models\DeviceRating;
use Illuminate\Http\Request;

class DeviceRatingController extends Controller
{
    public function index()
    {
        $device_ratings = DeviceRating::all();
        return view('device_ratings.list', compact('device_ratings'));
    }

    public function create()
    {
        return view('device_ratings.add');
    }

    public function store(DeviceRequest $request)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'status' => $statusCode]);

        DeviceRating::create($request->all());

        return redirect()->route('device_ratings.index')
            ->with('success', 'Device Rating created successfully.');
    }

    public function edit($id)
    {
        $device_rating = DeviceRating::findOrFail($id);
        return view('device_ratings.add', compact('device_rating'));
    }

    public function update(DeviceRequest $request, $id)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        DeviceRating::findOrFail($id)->update($request->all());

        return redirect()->route('device_ratings.index')
            ->with('success', 'Device Rating updated successfully.');
    }

    public function destroy($id)
    {
        DeviceRating::findOrFail($id)->delete();
        return redirect()->route('device_ratings.index')
            ->with('success', 'Device Rating deleted successfully.');
    }
}
