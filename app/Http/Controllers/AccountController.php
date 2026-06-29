<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::withCount('chats')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'waha_session_id' => 'required|string|max:255|unique:accounts,waha_session_id',
            'phone_number' => 'nullable|string|max:255',
            'base_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $account = Account::create($validated);
        return response()->json(['status' => 'success', 'account' => $account]);
    }

    public function update(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'waha_session_id' => 'required|string|max:255|unique:accounts,waha_session_id,' . $account->id,
            'phone_number' => 'nullable|string|max:255',
            'base_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $account->update($validated);
        return response()->json(['status' => 'success', 'account' => $account]);
    }

    public function destroy($id)
    {
        $account = Account::findOrFail($id);
        $account->delete();
        return response()->json(['status' => 'success']);
    }
}
