<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use StellarSecurity\SubscriptionLaravel\Enums\SubscriptionStatus;
use StellarSecurity\SubscriptionLaravel\Enums\SubscriptionType;
use StellarSecurity\SubscriptionLaravel\SubscriptionService;
use StellarSecurity\UserApiLaravel\UserService;

class LoginController extends Controller
{
    /**
     * Inject Stellar User + Subscription services from the packages.
     */
    public function __construct(
        private UserService $userService,
        private SubscriptionService $subscriptionService
    ) {
    }

    /**
     * Authenticate user against Stellar User API
     * and attach Antivirus subscription id (if any).
     */
    public function auth(Request $request): JsonResponse
    {
        $authResponse = $this->userService->auth([
            'username' => $request->input('username'),
            'password' => $request->input('password'),
        ]);

        $auth = $authResponse->object();

        $subscriptionId = 0;

        // Kun slå subscription op ved succesfuld auth
        if (($auth->response_code ?? null) === 200 && isset($auth->user->id)) {
            $subscriptionResponse = $this->subscriptionService
                ->findusersubscriptions($auth->user->id, SubscriptionType::ANTIVIRUS->value);

            $subscription = $subscriptionResponse->object();

            if (isset($subscription[0]->id)) {
                $subscriptionId = (int) $subscription[0]->id;
            }
        }

        $payload = [
            'response_code' => (int) ($auth->response_code ?? 500),
            'response_message' => (string) ($auth->response_message ?? 'Unknown error'),

            'user' => isset($auth->user) ? [
                'id' => (int) ($auth->user->id ?? 0),
                'name' => $auth->user->name ?? null,
                'token' => $auth->token ?? null,
            ] : null,
            'subscription_id' => $subscriptionId,
        ];

        $httpStatus = ($payload['response_code'] === 200) ? 200 : 401;

        return response()->json($payload, $httpStatus);
    }


    /**
     * Send reset-password link via Stellar User API.
     */
    public function sendresetpasswordlink(Request $request): JsonResponse
    {
        $email = $request->input('email');

        if ($email === null) {
            return response()->json([
                'response_code'    => 400,
                'response_message' => 'No email was provided',
            ]);
        }

        // Generate a confirmation code for the reset flow
        $confirmationCode = Str::random(32);

        // Use the package method (email + confirmation_code)
        $resetResponse = $this->userService
            ->sendresetpasswordlink($email, $confirmationCode)
            ->object();

        if (($resetResponse->response_code ?? 500) !== 200) {
            return response()->json([
                'response_code'    => $resetResponse->response_code ?? 500,
                'response_message' => $resetResponse->response_message ?? 'Unknown error',
            ]);
        }

        return response()->json([
            'response_code'    => 200,
            'response_message' => 'OK. Reset password link sent to your email.',
        ]);
    }

    /**
     * Create new Stellar user and attach Antivirus trial / subscription.
     */
    public function create(Request $request): JsonResponse
    {
        $username = $request->input('username');
        $password = $request->input('password');

        // Default trial length
        $days = 91;

        // Create user via Stellar User API
        $createResponse = $this->userService->create([
            'username' => $username,
            'password' => $password,
        ]);

        $auth = $createResponse->object();

        // Attach subscription_id for the client
        $auth->subscription_id = 0;

        if (isset($auth->user->id)) {
            // Create Antivirus subscription via Stellar Subscription API
            $subscriptionResponse = $this->subscriptionService->add([
                'user_id'    => $auth->user->id,
                'type'       => SubscriptionType::ANTIVIRUS->value,       // Antivirus product
                'status'     => SubscriptionStatus::ACTIVE->value,        // Immediately active
                'expires_at' => Carbon::now()->addDays($days),            // Trial / period length
            ]);

            $subscription = $subscriptionResponse->object();

            if (isset($subscription->id)) {
                $auth->subscription_id = $subscription->id;
            }
        }

        return response()->json($auth);
    }
}
