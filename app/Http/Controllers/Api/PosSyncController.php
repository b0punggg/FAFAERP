<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosSyncEvent;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosSyncController extends Controller
{
    public function push(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'events' => 'required|array|min:1',
            'events.*.idempotency_key' => 'required|uuid',
            'events.*.event_type' => 'required|string|max:64',
            'events.*.payload' => 'required|array',
        ]);

        $results = [];

        foreach ($data['events'] as $event) {
            $existing = PosSyncEvent::where('idempotency_key', $event['idempotency_key'])->first();
            if ($existing) {
                $results[] = ['idempotency_key' => $event['idempotency_key'], 'status' => $existing->status];

                continue;
            }

            PosSyncEvent::create([
                'branch_id' => $data['branch_id'],
                'idempotency_key' => $event['idempotency_key'],
                'event_type' => $event['event_type'],
                'payload' => $event['payload'],
                'status' => 'applied',
            ]);

            $results[] = ['idempotency_key' => $event['idempotency_key'], 'status' => 'applied'];
        }

        return response()->json(['results' => $results]);
    }
}
