<?php

namespace App\Http\Controllers;

use App\Models\DeviceInterface;
use Illuminate\Http\Request;

class DeviceInterfaceController extends Controller
{
    public function index()
    {
        $deviceInterfaces = DeviceInterface::all();
        return view('device_interface.list', compact('deviceInterfaces'));
    }
}
