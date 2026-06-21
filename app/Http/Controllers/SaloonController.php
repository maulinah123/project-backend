<?php

namespace App\Http\Controllers;

use App\Models\Saloon;
use App\Models\Service;
use Illuminate\Http\Request;

class SaloonController extends Controller
{
    public function index()
    {
        return response()->json(
            Saloon::with(['owner:id,name,email', 'services', 'bridalPackages'])
                ->latest()
                ->get()
        );
    }

    public function show($id)
    {
        return response()->json(
            Saloon::with(['owner:id,name,email', 'services', 'bridalPackages'])
                ->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'location' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('saloons', 'public')
            : null;

        $saloon = Saloon::create([
            'owner_id' => auth()->id(),
            'name' => $request->name,
            'location' => $request->location,
            'description' => $request->description,
            'image' => $imagePath,
        ]);

        return response()->json($saloon, 201);
    }

    public function createAndAddService(Request $request, $saloonId)
    {
        $saloon = Saloon::findOrFail($saloonId);

        if ($saloon->owner_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('services', 'public')
            : null;

        $service = Service::create([
            'saloon_id' => $saloon->id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration' => $request->duration,
            'image' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Service created and added to your saloon.',
            'service' => $service,
        ], 201);
    }

    public function mySaloons()
    {
        return response()->json(
            Saloon::with(['services', 'bridalPackages'])
                ->where('owner_id', auth()->id())
                ->latest()
                ->get()
        );
    }

    public function myServices($saloonId)
    {
        $saloon = Saloon::where('owner_id', auth()->id())->findOrFail($saloonId);

        return response()->json(
            Service::where('saloon_id', $saloon->id)->get()
        );
    }
}
