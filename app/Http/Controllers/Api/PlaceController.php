<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Place::query();
        if ($request->has('name')) {
            $query->where('name', 'ilike', '%'.$request->get('name').'%');
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'city'  => 'required|string|max:255',
            'state' => 'required|string|max:255',
        ]);
        $data['slug'] = Str::slug($data['name']);
        $place = Place::create($data);
        return response()->json($place, 201);
    }

    public function show($id)
    {
        $place = Place::findOrFail($id);
        return response()->json($place);
    }

    public function update(Request $request, $id)
    {
        $place = Place::findOrFail($id);
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'city'  => 'required|string|max:255',
            'state' => 'required|string|max:255',
        ]);
        $data['slug'] = Str::slug($data['name']);
        $place->update($data);
        return response()->json($place);
    }

    public function destroy($id)
    {
        $place = Place::findOrFail($id);
        $place->delete();
        return response()->json(null, 204);
    }
}
