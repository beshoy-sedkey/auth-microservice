<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Otp;
use App\Models\User;
use Twilio\Rest\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Notifications\SmsNotification;
use App\Http\Requests\RegistrationRequest;
use Spatie\FlareClient\Http\Exceptions\NotFound;

class AuthController extends Controller
{
    /**
     * @param RegistrationRequest $request
     *
     * @return JsonResponse
     */
    public function register(RegistrationRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $tokenResult = $user->createToken('Personal Access Token')->accessToken;

        Redis::rpush('user_service_queue', json_encode([
            'event' => 'user_registered',
            'user' => $user->toArray(),
        ]));

        return response()->json([
            'user' => $user,
            'token' => $tokenResult->token
        ], 201);
    }

    /**
     * @param LoginRequest $request
     *
     * @return [type]
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user && \Hash::check($request->password, $user->password)) {
            $token = $user->createToken('Personal Access Token')->accessToken;
            Redis::publish('user_logged_in', $user);
            return response()->json([
                'user' => $user,
                'token' => $token->token
            ], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);
            $user = User::where('email', $request->email)->first();
            $user->sendOTPNotification();
        } catch (\Throwable $th) {
            throw $th;
        }
        return response()->json(['message' => 'OTP sent to your phone.'], 200);
    }

    /**
     * @param mixed $userId
     * @param mixed $inputOtp
     *
     * @return [type]
     */
    public function verifyOtp(Request $request)
    {
        try {
            $otp = Otp::where('otp_code', $request->otp)
            ->whereNull('used_at')
            ->first();
            if (!$otp) {
                return response()->json(['message' => 'OTP Not Exist'] , 404);
            }elseif ($otp->expires_at->isPast()) {
                return response()->json(['message' => 'This Otp has been expired'] , 500);
            }
            $otp->update(['used_at' => Carbon::now()]);
        } catch (\Throwable $th) {
            throw $th;
        }

        return response()->json(["message" => "Verified!" ]);
    }


    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $user = User::where('phone' , $request->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'User Not Found !'] , 404);
        }
        $user->sendOTPNotification();
        return response()->json(['message' => 'OTP sent to your phone.'], 200);
    }
}
