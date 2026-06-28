<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\EnquiryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/admin/login',  [AuthController::class, 'adminLogin'])->middleware('throttle:10,1');
    Route::post('/cs/login',     [AuthController::class, 'csLogin'])->middleware('throttle:10,1');
    Route::post('/client/login', [AuthController::class, 'clientLogin'])->middleware('throttle:10,1');
    Route::post('/register',     [AuthController::class, 'clientRegister'])->middleware('throttle:5,60');
});

Route::get('/products',           [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,60');

// Public receipt lookup (for non-registered users to download)
Route::get('/receipts/{token}', [OrderController::class, 'publicReceipt']);

// ── Authenticated (any valid token) ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Orders (clients see their own; staff/admin see all via /admin/orders)
    Route::get('/orders',         [OrderController::class, 'index']);
    Route::post('/orders',        [OrderController::class, 'store'])->middleware('throttle:20,60');
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Enquiries
    Route::get('/enquiries',                     [EnquiryController::class, 'index']);
    Route::post('/enquiries',                    [EnquiryController::class, 'store'])->middleware('throttle:10,60');
    Route::get('/enquiries/{enquiry}',           [EnquiryController::class, 'show']);
    Route::post('/enquiries/{enquiry}/messages', [EnquiryController::class, 'sendMessage'])->middleware('throttle:30,1');

    // ── Staff routes (Admin + CS) ────────────────────────────────────────────
    Route::middleware('staff')->group(function () {
        // Dashboard stats
        Route::get('/admin/stats',  [AdminController::class, 'stats']);

        // Client management
        Route::get('/admin/clients',                 [AdminController::class, 'clients']);
        Route::post('/admin/clients/{user}/approve', [AdminController::class, 'approveClient']);
        Route::post('/admin/clients/{user}/reject',  [AdminController::class, 'rejectClient']);

        // Order management
        Route::get('/admin/orders',                    [OrderController::class, 'staffIndex']);
        Route::put('/admin/orders/{order}/status',     [OrderController::class, 'updateStatus']);

        // Enquiry management
        Route::put('/admin/enquiries/{enquiry}/status', [EnquiryController::class, 'updateStatus']);

        // Products (staff CRUD)
        Route::post('/admin/products',             [ProductController::class, 'store']);
        Route::put('/admin/products/{product}',    [ProductController::class, 'update']);
        Route::delete('/admin/products/{product}', [ProductController::class, 'destroy']);

        // Contact forms
        Route::get('/admin/contact-forms',                      [AdminController::class, 'contactForms']);
        Route::put('/admin/contact-forms/{contactForm}/status', [AdminController::class, 'updateContactForm']);

        // Records / activity log
        Route::get('/admin/records', [AdminController::class, 'records']);
    });

    // ── Admin-only ───────────────────────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::get('/admin/staff',                    [AdminController::class, 'staffList']);
        Route::post('/admin/staff',                   [AdminController::class, 'createStaff']);
        Route::delete('/admin/staff/{user}',          [AdminController::class, 'deleteStaff']);
    });
});
