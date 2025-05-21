<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Api\Admin\ProgramCommentController as AdminProgramCommentController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\ReportCommentController as AdminReportCommentController;
use App\Http\Controllers\Api\Admin\DonationController as AdminDonationController;

// Verify email route
Route::get('/auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

// Donation notification route
Route::post('/donations/notification', [DonationController::class, 'handleNotification']);

Route::middleware('apikey')->group(function () {
    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Category
    Route::get('/categories', [CategoryController::class, 'index']);

    // Program
    Route::get('/programs', [ProgramController::class, 'index']);
    Route::get('/programs/{slug}', [ProgramController::class, 'show']);
    Route::get('/programs/category/{categorySlug}', [ProgramController::class, 'showByCategory']);

    // Report
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{slug}', [ReportController::class, 'show']);
    Route::get('/reports/category/{categorySlug}', [ReportController::class, 'showByCategory']);

    // Donation
    Route::post('/donations', [DonationController::class, 'store']);
    Route::get('/donations/status/{invoiceNumber}', [DonationController::class, 'status']);
});

Route::middleware(['apikey', 'auth.jwt'])->group(function () {
    // User Profile
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'update']);
    Route::delete('/user/profile', [UserController::class, 'destroy']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/email/verify/resend', [AuthController::class, 'resendVerificationEmail']);

    // Program Comment
    Route::post('/programs/{programId}/comments', [\App\Http\Controllers\Api\Comment\ProgramCommentController::class, 'store']);
    Route::put('/program-comments/{commentId}', [\App\Http\Controllers\Api\Comment\ProgramCommentController::class, 'update']);
    Route::delete('/program-comments/{commentId}', [\App\Http\Controllers\Api\Comment\ProgramCommentController::class, 'destroy']);

    // Report Comment
    Route::post('/reports/{reportId}/comments', [\App\Http\Controllers\Api\Comment\ReportCommentController::class, 'store']);
    Route::put('/report-comments/{commentId}', [\App\Http\Controllers\Api\Comment\ReportCommentController::class, 'update']);
    Route::delete('/report-comments/{commentId}', [\App\Http\Controllers\Api\Comment\ReportCommentController::class, 'destroy']);

    // Donation History
    Route::get('/donations/history', [DonationController::class, 'history']);
});

Route::prefix('admin')->middleware(['apikey', 'auth.jwt', 'admin'])->group(function () {
    // User Management
    Route::apiResource('users', AdminUserController::class);
    Route::post('users/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
    Route::post('users/{id}/verify-email', [AdminUserController::class, 'verifyEmail']);
    Route::post('users/{id}/send-verification-email', [AdminUserController::class, 'sendVerificationEmail']);
    Route::post('users/{id}/send-reset-password-email', [AdminUserController::class, 'sendResetPasswordEmail']);

    // Category Management
    Route::apiResource('categories', AdminCategoryController::class);

    // Program Management
    Route::apiResource('programs', AdminProgramController::class);

    // Program Comment Management
    Route::apiResource('program-comments', AdminProgramCommentController::class);

    // Report Management
    Route::apiResource('reports', AdminReportController::class);

    // Report Comment Management
    Route::apiResource('report-comments', AdminReportCommentController::class);

    // Donation Management
    Route::apiResource('donations', AdminDonationController::class)->except('store');
});
