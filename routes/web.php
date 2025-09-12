<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Applications
    Route::resource('applications', ApplicationController::class);
    Route::post('applications/{application}/submit', [ApplicationController::class, 'submit'])->name('applications.submit');
    Route::post('applications/{application}/cancel', [ApplicationController::class, 'cancel'])->name('applications.cancel');
    Route::get('pending-applications', [ApplicationController::class, 'pending'])->name('applications.pending');
    Route::get('my-approvals', [ApplicationController::class, 'myApprovals'])->name('applications.my-approvals');

    // Approvals
    Route::post('approvals/{approval}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('approvals/{approval}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    Route::post('approvals/{approval}/skip', [ApprovalController::class, 'skip'])->name('approvals.skip');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/settings', [NotificationController::class, 'settings'])->name('notifications.settings');
    Route::post('notifications/settings', [NotificationController::class, 'updateSettings'])->name('notifications.update-settings');
    Route::post('notifications/mark-as-read/{id?}', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::get('notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('notifications/test', [NotificationController::class, 'testNotification'])->name('notifications.test');
});

// Auth Routes (Laravel Breeze or manual implementation)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'ログイン情報が正しくありません。',
    ]);
});

Route::post('/logout', function (Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');