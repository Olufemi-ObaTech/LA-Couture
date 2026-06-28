<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMessage;
use App\Models\User;
use Illuminate\Http\Request;

class StaffMessageController extends Controller
{
    public function inbox(Request $request)
    {
        $messages = StaffMessage::with('from')
            ->where('to_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($messages);
    }

    public function sent(Request $request)
    {
        $messages = StaffMessage::with('to')
            ->where('from_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($messages);
    }

    public function unread(Request $request)
    {
        $count = StaffMessage::where('to_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function recipients(Request $request)
    {
        $staff = User::whereIn('role', ['admin', 'cs'])
            ->where('id', '!=', $request->user()->id)
            ->get(['id', 'name', 'email', 'role']);

        return response()->json($staff);
    }

    public function store(Request $request)
    {
        $request->validate([
            'to_id'         => 'required|integer|exists:users,id',
            'subject'       => 'required|string|max:200',
            'body'          => 'required|string|max:5000',
            'replied_to_id' => 'nullable|integer|exists:staff_messages,id',
        ]);

        $recipient = User::findOrFail($request->to_id);

        if (! $recipient->isStaff()) {
            return response()->json(['message' => 'You can only message other staff members here.'], 400);
        }

        $msg = StaffMessage::create([
            'from_id'       => $request->user()->id,
            'to_id'         => $request->to_id,
            'subject'       => strip_tags($request->subject),
            'body'          => strip_tags($request->body),
            'replied_to_id' => $request->replied_to_id,
        ]);

        return response()->json($msg->load('from', 'to'), 201);
    }

    public function show(Request $request, StaffMessage $staffMessage)
    {
        $user = $request->user();

        if ($staffMessage->to_id !== $user->id && $staffMessage->from_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($staffMessage->to_id === $user->id && ! $staffMessage->read_at) {
            $staffMessage->update(['read_at' => now()]);
        }

        return response()->json($staffMessage->load('from', 'to'));
    }

    public function destroy(Request $request, StaffMessage $staffMessage)
    {
        $user = $request->user();

        if ($staffMessage->from_id !== $user->id && $staffMessage->to_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $staffMessage->delete();

        return response()->json(['message' => 'Message deleted.']);
    }
}
