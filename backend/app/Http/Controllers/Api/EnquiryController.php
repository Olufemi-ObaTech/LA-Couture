<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\EnquiryMessage;
use App\Models\User;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            $enquiries = Enquiry::with(['user', 'messages'])
                                ->orderByDesc('updated_at')
                                ->get();
        } else {
            $enquiries = Enquiry::with('messages')
                                ->where('user_id', $user->id)
                                ->orderByDesc('updated_at')
                                ->get();
        }

        return response()->json($enquiries);
    }

    public function show(Request $request, Enquiry $enquiry)
    {
        $user = $request->user();

        if (! $user->isStaff() && $enquiry->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Mark messages from the other party as read
        $otherType = $user->isStaff() ? 'client' : 'staff';
        EnquiryMessage::where('enquiry_id', $enquiry->id)
                      ->where('sender_type', $otherType)
                      ->update(['is_read' => true]);

        return response()->json($enquiry->load('messages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'     => 'required|string|max:200',
            'category'    => 'required|string|max:50',
            'description' => 'required|string|max:5000',
        ]);

        $user = $request->user();

        $enquiry = Enquiry::create([
            'user_id'      => $user->id,
            'client_name'  => $user->name,
            'client_email' => $user->email,
            'subject'      => strip_tags($request->subject),
            'category'     => strip_tags($request->category),
            'description'  => strip_tags($request->description),
            'status'       => 'open',
        ]);

        EnquiryMessage::create([
            'enquiry_id'  => $enquiry->id,
            'user_id'     => $user->id,
            'sender_type' => 'client',
            'sender_name' => $user->name,
            'message'     => strip_tags($request->description),
        ]);

        return response()->json($enquiry->load('messages'), 201);
    }

    public function sendMessage(Request $request, Enquiry $enquiry)
    {
        $user = $request->user();

        if (! $user->isStaff() && $enquiry->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        // Determine sender type: admin, cs, or client
        $senderType = $user->isStaff() ? $user->role : 'client';

        $message = EnquiryMessage::create([
            'enquiry_id'  => $enquiry->id,
            'user_id'     => $user->id,
            'sender_type' => $senderType,
            'sender_name' => $user->name,
            'message'     => strip_tags($request->message),
        ]);

        $enquiry->touch();

        return response()->json($message, 201);
    }

    // Staff can initiate a new message thread to a specific client
    public function staffInitiate(Request $request, User $user)
    {
        if ($user->role !== 'client') {
            return response()->json(['message' => 'Target must be a client.'], 400);
        }

        $request->validate([
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:5000',
        ]);

        $staff = $request->user();

        $enquiry = Enquiry::create([
            'user_id'      => $user->id,
            'client_name'  => $user->name,
            'client_email' => $user->email,
            'subject'      => strip_tags($request->subject),
            'category'     => 'staff-initiated',
            'description'  => strip_tags($request->message),
            'status'       => 'open',
        ]);

        EnquiryMessage::create([
            'enquiry_id'  => $enquiry->id,
            'user_id'     => $staff->id,
            'sender_type' => $staff->role,
            'sender_name' => $staff->name . ' (L.A. Couture)',
            'message'     => strip_tags($request->message),
        ]);

        return response()->json($enquiry->load('messages'), 201);
    }

    public function updateStatus(Request $request, Enquiry $enquiry)
    {
        $request->validate([
            'status' => 'required|in:open,in-progress,closed',
        ]);

        $enquiry->update(['status' => $request->status]);

        return response()->json($enquiry);
    }
}
