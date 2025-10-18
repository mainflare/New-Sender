<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function dashboard()
    {
        $stats = [
            'totalUsers' => User::count(),
            'activeSubscriptions' => Subscription::where('status', 'active')->count(),
            'monthlyRevenue' => Payment::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->where('status', 'completed')
                ->sum('amount') / 100, // Convert from cents
        ];

        $recentUsers = User::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }

    /**
     * Show the users management page.
     */
    public function users()
    {
        $users = User::with('subscription')->paginate(15);
        
        return view('admin.users', compact('users'));
    }

    /**
     * Show the subscriptions management page.
     */
    public function subscriptions()
    {
        $subscriptions = Subscription::with('user')->paginate(15);
        
        return view('admin.subscriptions', compact('subscriptions'));
    }

    /**
     * Show the admin settings page.
     */
    public function settings()
    {
        return view('admin.settings');
    }
}
