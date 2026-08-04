<?php

namespace App\Http\Controllers;

use App\Models\BridalPackage;
use App\Models\Saloon;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Return every bridal package, including the salon it belongs to.
     */
    public function index()
    {
        return response()->json(
            BridalPackage::with('saloon:id,name,location,image')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'saloon_id' => 'required|exists:saloons,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'package_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        $saloon = Saloon::findOrFail($request->saloon_id);

        if ($saloon->owner_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('bridal-packages', 'public')
            : null;

        $package = BridalPackage::create([
            'saloon_id' => $saloon->id,
            'name' => $request->name,
            'description' => $request->description,
            'package_price' => $request->package_price,
            'image' => $imagePath,
        ]);

        return response()->json($package, 201);
    }

    public function myPackages()
    {
        $ownerId = auth()->id();

        return response()->json(
            BridalPackage::whereHas('saloon', fn ($query) => $query->where('owner_id', $ownerId))
                ->with('saloon:id,owner_id,name,location,image')
                ->latest()
                ->get()
        );
    }
}
