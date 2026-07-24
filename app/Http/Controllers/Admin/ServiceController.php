<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\RenderCache;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return view('admin.services.index', [
            'services' => Service::withCount(['pages'])
                ->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'price_from' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:300'],
            'image' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $service->update($data);

        cache()->forget('daya:katalog:layanan');
        RenderCache::bump();

        return back()->with('status', "Layanan \"{$service->name}\" diperbarui.");
    }
}
