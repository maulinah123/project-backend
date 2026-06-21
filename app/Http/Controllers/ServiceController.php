<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // Get all global services (e.g., Makeup, Hair, Nails)
    public function index()
    {
        return response()->json(Service::all());
    }

    public function show($id)
    {
        return response()->json(
            Service::with(['saloons.owner:id,name,email'])->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('services', 'public')
            : null;

        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
        ]);

        return response()->json($service, 201);
    }

    // Get all salons offering a specific service (Flow 2)
    public function getSaloonsByService($serviceId)
    {
        $service = Service::findOrFail($serviceId);

        // Return service with all linked saloons and pivot data (price/duration)
        $saloons = $service->saloons()->with('owner:id,name,email')->get();

        return response()->json([
            'service' => $service->name,
            'available_saloons' => $saloons,
        ]);
    }
}
