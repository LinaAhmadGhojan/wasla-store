<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExternalShopping\CreateExternalPlatformAction;
use App\Actions\ExternalShopping\UpdateExternalPlatformAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExternalPlatformRequest;
use App\Http\Requests\Admin\UpdateExternalPlatformRequest;
use App\Models\ExternalPlatform;
use Illuminate\Http\Request;

class ExternalPlatformController extends Controller
{
    public function index(Request $request)
    {
        $query = ExternalPlatform::withCount('purchaseRequests')->orderBy('name');

        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('admin.external-platforms.index', [
            'platforms' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.external-platforms.create', [
            'platform' => new ExternalPlatform(),
        ]);
    }

    public function store(StoreExternalPlatformRequest $request, CreateExternalPlatformAction $action)
    {
        $action->execute($request->validated());

        return redirect()->route('admin.external-platforms.index')->with('success', 'Platform created successfully.');
    }

    public function edit(ExternalPlatform $externalPlatform)
    {
        return view('admin.external-platforms.edit', [
            'platform' => $externalPlatform,
        ]);
    }

    public function update(UpdateExternalPlatformRequest $request, ExternalPlatform $externalPlatform, UpdateExternalPlatformAction $action)
    {
        $action->execute($externalPlatform, $request->validated());

        return redirect()->route('admin.external-platforms.index')->with('success', 'Platform updated successfully.');
    }

    public function destroy(ExternalPlatform $externalPlatform)
    {
        $externalPlatform->delete();

        return redirect()->route('admin.external-platforms.index')->with('success', 'Platform deleted successfully.');
    }
}
