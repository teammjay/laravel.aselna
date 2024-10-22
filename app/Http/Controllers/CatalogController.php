<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class CatalogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        //$adminlte = Config::get('adminlte');
        return view('catalog.list');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('catalog.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
