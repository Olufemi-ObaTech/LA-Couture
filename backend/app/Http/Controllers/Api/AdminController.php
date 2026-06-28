<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactForm;
use App\Models\Enquiry;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'approved_clients' => User::where('role', 'client')->where('status', 'approved')->count(),
            'pending_clients'  => User::where('role', 'client')->where('status', 'pending')->count(),
            'total_orders'     => Order::count(),
            'pending_orders'   => Order::where('status', 'pending')->count(),
            'open_enquiries'   => Enquiry::where('status', 'open')->count(),
            'contact_forms'    => ContactForm::count(),
            'total_revenue'    => Order::where('status', 'paid')->sum('total'),
            'total_clients'    => User::where('role', 'client')->count(),
        ]);
    }

    public function clients(Request $request)
    {
        $clients = User::where('role', 'client')
                       ->orderByDesc('created_at')
                       ->get(['id','name','email','brand_email','phone','status','interests','created_at','approved_at','rejected_at','rejection_reason']);

        return response()->json($clients);
    }

    public function approveClient(Request $request, User $user)
    {
        if ($user->role !== 'client') {
            return response()->json(['message' => 'Not a client account.'], 400);
        }

        $user->update(['status' => 'approved', 'approved_at' => now(), 'rejection_reason' => null]);

        return response()->json(['message' => 'Client approved.', 'user' => $user]);
    }

    public function rejectClient(Request $request, User $user)
    {
        if ($user->role !== 'client') {
            return response()->json(['message' => 'Not a client account.'], 400);
        }

        $request->validate(['reason' => 'nullable|string|max:500']);

        $user->update([
            'status'           => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => $request->reason ? strip_tags($request->reason) : 'No reason provided.',
        ]);

        return response()->json(['message' => 'Client rejected.', 'user' => $user]);
    }

    public function contactForms()
    {
        return response()->json(ContactForm::orderByDesc('created_at')->paginate(50));
    }

    public function updateContactForm(Request $request, ContactForm $contactForm)
    {
        $request->validate(['status' => 'required|in:received,reviewed,responded']);
        $contactForm->update(['status' => $request->status]);
        return response()->json($contactForm);
    }

    public function records()
    {
        return response()->json([
            'clients'       => User::where('role', 'client')->orderByDesc('created_at')
                                   ->get(['id','name','email','brand_email','phone','status','created_at','approved_at']),
            'orders'        => Order::with('items')->orderByDesc('created_at')->take(100)->get(),
            'enquiries'     => Enquiry::with('messages')->orderByDesc('updated_at')->take(100)->get(),
            'contact_forms' => ContactForm::orderByDesc('created_at')->take(100)->get(),
        ]);
    }

    // ── Admin-only: staff management ────────────────────────────────────────

    public function staffList()
    {
        $staff = User::whereIn('role', ['admin', 'cs'])
                     ->orderByDesc('created_at')
                     ->get(['id','name','email','role','status','created_at']);

        return response()->json($staff);
    }

    public function createStaff(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:users,email',
            'role'     => 'required|in:cs,admin',
            'password' => 'required|string|min:8',
        ]);

        $staff = User::create([
            'name'     => strip_tags($request->name),
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => 'approved',
        ]);

        return response()->json(['message' => 'Staff account created.', 'user' => $staff], 201);
    }

    public function deleteStaff(Request $request, User $user)
    {
        if (! in_array($user->role, ['admin', 'cs'])) {
            return response()->json(['message' => 'Not a staff account.'], 400);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete your own account.'], 400);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Staff account removed.']);
    }
}
