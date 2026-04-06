<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientServer;
use App\Services\SshActivityService;
use Illuminate\Http\Request;

class ClientServerController extends Controller
{
    public function __construct(
        private SshActivityService $sshService,
    ) {}

    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'auth_type' => 'required|in:key,password',
            'private_key_path' => 'required_if:auth_type,key|nullable|string|max:500',
            'password' => 'required_if:auth_type,password|nullable|string',
        ]);

        // Check for duplicate
        $exists = $client->servers()
            ->where('host', $validated['host'])
            ->where('username', $validated['username'] ?? 'root')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This server/username combination is already linked to this client.'], 422);
        }

        // Build server temporarily to test connection
        $server = new ClientServer([
            'label' => $validated['label'],
            'host' => $validated['host'],
            'port' => $validated['port'] ?? 22,
            'username' => $validated['username'] ?? 'root',
            'auth_type' => $validated['auth_type'],
            'private_key_path' => $validated['private_key_path'] ?? null,
        ]);

        if ($validated['auth_type'] === 'password' && !empty($validated['password'])) {
            $server->encrypted_password = $validated['password'];
        }

        // Test connection before saving
        $result = $this->sshService->testConnection($server);
        if (!$result['success']) {
            return response()->json(['message' => 'SSH connection failed: ' . ($result['error'] ?? 'Unknown error')], 422);
        }

        $server->client_id = $client->id;
        $server->save();

        return response()->json([
            'server' => [
                'id' => $server->id,
                'label' => $server->label,
                'host' => $server->host,
                'port' => $server->port,
                'username' => $server->username,
                'auth_type' => $server->auth_type,
                'is_active' => $server->is_active,
                'display_name' => $server->display_name,
            ],
        ]);
    }

    public function destroy(Client $client, ClientServer $server)
    {
        if ($server->client_id !== $client->id) {
            return response()->json(['message' => 'Server does not belong to this client.'], 403);
        }

        $server->delete();

        return response()->json(['success' => true]);
    }

    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'auth_type' => 'required|in:key,password',
            'private_key_path' => 'required_if:auth_type,key|nullable|string|max:500',
            'password' => 'required_if:auth_type,password|nullable|string',
        ]);

        $server = new ClientServer([
            'label' => 'Test',
            'host' => $validated['host'],
            'port' => $validated['port'] ?? 22,
            'username' => $validated['username'] ?? 'root',
            'auth_type' => $validated['auth_type'],
            'private_key_path' => $validated['private_key_path'] ?? null,
        ]);

        if ($validated['auth_type'] === 'password' && !empty($validated['password'])) {
            $server->encrypted_password = $validated['password'];
        }

        $result = $this->sshService->testConnection($server);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Connection successful!' : ('Connection failed: ' . ($result['error'] ?? 'Unknown error')),
        ]);
    }
}
