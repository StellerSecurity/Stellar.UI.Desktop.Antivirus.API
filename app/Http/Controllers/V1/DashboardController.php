<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Http\Response;
use StellarSecurity\SubscriptionLaravel\Enums\SubscriptionType;
use StellarSecurity\SubscriptionLaravel\SubscriptionService;
use StellarSecurity\UserApiLaravel\UserService;

class DashboardController
{

    public function __construct(
        private UserService $userService,
        private SubscriptionService $subscriptionService
    ) {
    }

    /**
     * Return basic user + subscription info for the desktop antivirus client.
     *
     * Expects a personal token and resolves:
     * - user via User API
     * - subscription via Subscription API (ANTIVIRUS)
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function home(Request $request)
    {
        $token = $request->input('token');

        if (empty($token)) {
            return response('', 401);
        }

        // Resolve personal token → get tokenable (user) id
        $userTokenResponse = $this->userService->token($token);

        if ($userTokenResponse->failed()) {
            return response('', 401);
        }

        $userToken = $userTokenResponse->object();

        if (empty($userToken?->token?->tokenable_id)) {
            return response('', 401);
        }

        $userId = $userToken->token->tokenable_id;


        // Fetch user
        $userResponse = $this->userService->user($userId);

        if ($userResponse->failed()) {
            return response('', 502);
        }

        $user = $userResponse->object();


        if(!isset($user->user->id)) {
            return response('', 200);
        }

        $user = $user->user;

        // Fetch subscription for this user + ANTIVIRUS type
        $subscriptionResponse = $this->subscriptionService->findUserSubscriptions(
            $userId,
            SubscriptionType::ANTIVIRUS->value
        );

        if ($subscriptionResponse->failed()) {
            return response('', 502);
        }

        $subscriptionData = $subscriptionResponse->object();

        if (is_array($subscriptionData)) {
            $subscription = $subscriptionData[0] ?? null;
        } else {
            $subscription = $subscriptionData;
        }

        return response()->json([
            'user' => ['email' => $user->email],
            'subscription' => [
                'expires_at' => $subscription->expires_at,
                'active'     => $subscription->status,
            ],
        ], 200);
    }

}
