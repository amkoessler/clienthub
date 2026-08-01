<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Client::where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('name', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('email', 'LIKE', '%'.$request->search.'%');
            });
        }

        return ClientResource::collection($query->paginate(15));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'active';

        $client = Client::create($data);

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $id): ClientResource
    {
        $client = Client::findOrFail($id);

        if ($client->user_id !== $request->user()->id) {
            abort(404);
        }

        return new ClientResource($client);
    }

    public function update(UpdateClientRequest $request, string $id): ClientResource
    {
        $client = Client::findOrFail($id);

        if ($client->user_id !== $request->user()->id) {
            abort(404);
        }

        $client->update($request->validated());

        return new ClientResource($client);
    }

    public function destroy(Request $request, string $id): Response
    {
        $client = Client::findOrFail($id);

        if ($client->user_id !== $request->user()->id) {
            abort(404);
        }

        $client->delete();

        return response()->noContent();
    }
}
