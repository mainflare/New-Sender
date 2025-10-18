<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Message;

class UserController extends Controller
{
    /**
     * Show the user dashboard.
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        $stats = [
            'totalCampaigns' => Campaign::where('user_id', $user->id)->count(),
            'openConversations' => Conversation::where('user_id', $user->id)
                ->where('status', 'open')->count(),
            'totalContacts' => Contact::where('user_id', $user->id)->count(),
            'messagesSent' => Message::where('user_id', $user->id)->count(),
        ];

        $recentCampaigns = Campaign::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $recentConversations = Conversation::where('user_id', $user->id)
            ->with('contact')
            ->latest()
            ->take(5)
            ->get();

        return view('user.dashboard', compact('stats', 'recentCampaigns', 'recentConversations'));
    }

    /**
     * Show the conversations page.
     */
    public function conversations()
    {
        $conversations = Conversation::where('user_id', auth()->id())
            ->with('contact')
            ->paginate(15);
        
        return view('user.conversations', compact('conversations'));
    }

    /**
     * Show the contacts page.
     */
    public function contacts()
    {
        $contacts = Contact::where('user_id', auth()->id())->paginate(15);
        
        return view('user.contacts', compact('contacts'));
    }

    /**
     * Show the campaigns page.
     */
    public function campaigns()
    {
        $campaigns = Campaign::where('user_id', auth()->id())->paginate(15);
        
        return view('user.campaigns', compact('campaigns'));
    }

    /**
     * Show the WhatsApp sessions page.
     */
    public function whatsapp()
    {
        return view('user.whatsapp');
    }

    /**
     * Show the chatbots page.
     */
    public function chatbots()
    {
        return view('user.chatbots');
    }

    /**
     * Show the analytics page.
     */
    public function analytics()
    {
        return view('user.analytics');
    }

    /**
     * Show the user settings page.
     */
    public function settings()
    {
        return view('user.settings');
    }
}
