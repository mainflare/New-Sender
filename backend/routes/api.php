<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\WhatsappSessionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\GoogleMapsScraperController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'WhatsML Backend API is running',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

                                                                                                                                                                                                                                                                                                                            // Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // Workspaces
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::post('/workspaces/{id}/invite', [WorkspaceController::class, 'inviteMember']);
    
    // WhatsApp Sessions                                                                                                                            
    Route::apiResource('whatsapp-sessions', WhatsappSessionController::class);                                                                                                      
    Route::get('/whatsapp-sessions/{id}/qr', [WhatsappSessionController::class, 'getQRCode']);
    Route::post('/whatsapp-sessions/{id}/disconnect', [WhatsappSessionController::class, 'disconnect']);
    
    // Contacts
    Route::apiResource('contacts', ContactController::class);
    Route::post('/contacts/import', [ContactController::class, 'import']);
    Route::post('/contacts/validate', [ContactController::class, 'validate']);
    Route::post('/contacts/validate-bulk', [ContactController::class, 'validateBulk']);
    
    // Google Maps Scraper
    Route::post('/scraper/search-places', [GoogleMapsScraperController::class, 'searchPlaces']);
    Route::post('/scraper/place-details', [GoogleMapsScraperController::class, 'getPlaceDetails']);
    Route::post('/scraper/search-nearby', [GoogleMapsScraperController::class, 'searchNearby']);
    Route::post('/scraper/next-page', [GoogleMapsScraperController::class, 'getNextPage']);
    Route::post('/scraper/scrape-and-save', [GoogleMapsScraperController::class, 'scrapeAndSave']);
    Route::post('/scraper/scrape-single', [GoogleMapsScraperController::class, 'scrapeSingleBusiness']);
    
    // Campaigns
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('/campaigns/{id}/start', [CampaignController::class, 'start']);
    Route::post('/campaigns/{id}/pause', [CampaignController::class, 'pause']);
    Route::post('/campaigns/{id}/resume', [CampaignController::class, 'resume']);
    Route::get('/campaigns/{id}/analytics', [CampaignController::class, 'analytics']);
    
    // Conversations
    Route::apiResource('conversations', ConversationController::class);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/send-message', [ConversationController::class, 'sendMessage']);
    Route::post('/conversations/{id}/assign', [ConversationController::class, 'assign']);
    Route::post('/conversations/{id}/labels', [ConversationController::class, 'addLabel']);
    
    // Chatbots
    Route::apiResource('chatbots', ChatbotController::class);
    Route::post('/chatbots/{id}/train', [ChatbotController::class, 'train']);
    Route::post('/chatbots/{id}/test', [ChatbotController::class, 'test']);
    Route::get('/chatbots/{id}/training-data', [ChatbotController::class, 'trainingData']);
    
    // Templates
    Route::apiResource('templates', TemplateController::class);
    Route::post('/templates/{id}/use', [TemplateController::class, 'use']);
    
    // Payments
    Route::post('/payments/create-intent', [PaymentController::class, 'createPaymentIntent']);
    Route::post('/payments/process', [PaymentController::class, 'processPayment']);
    Route::get('/payments/history', [PaymentController::class, 'getPaymentHistory']);
    
    // Subscriptions
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'getPlans']);
    Route::get('/subscriptions/current', [SubscriptionController::class, 'getCurrentSubscription']);
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::put('/subscriptions/change', [SubscriptionController::class, 'changeSubscription']);
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancelSubscription']);
    Route::get('/subscriptions/limits', [SubscriptionController::class, 'checkLimits']);
    
    // Assets
    Route::apiResource('assets', AssetController::class);
    Route::post('/assets/{id}/use', [AssetController::class, 'incrementUsage']);
    Route::get('/assets/stats/workspace', [AssetController::class, 'getStatistics']);
    
    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/statistics', [ActivityLogController::class, 'statistics']);
    Route::delete('/activity-logs/cleanup', [ActivityLogController::class, 'cleanup']);
    
    // Analytics
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/analytics/campaigns', [AnalyticsController::class, 'campaignAnalytics']);
    Route::get('/analytics/messages', [AnalyticsController::class, 'messageAnalytics']);
    Route::get('/analytics/admin', [AnalyticsController::class, 'adminStats']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'deleteAll']);
    Route::get('/notifications/settings', [NotificationController::class, 'getSettings']);
    Route::put('/notifications/settings', [NotificationController::class, 'updateSettings']);
    
    // Admin Panel (Super Admin Only)
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/users/{id}', [AdminController::class, 'getUserDetails']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/subscriptions', [AdminController::class, 'getSubscriptions']);
        Route::post('/subscriptions/{id}/cancel', [AdminController::class, 'cancelSubscription']);
        Route::get('/payments', [AdminController::class, 'getPayments']);
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);
        Route::get('/system-health', [AdminController::class, 'systemHealth']);
        Route::get('/audit-logs', [AdminController::class, 'getAuditLogs']);
    });
});

// Stripe webhook (no authentication required)
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook']);

