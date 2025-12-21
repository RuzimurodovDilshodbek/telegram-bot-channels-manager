<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PollParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpCollectorController extends Controller
{
    /**
     * Show IP collection page with ReCaptcha
     */
    public function show(Request $request)
    {
        $token = $request->query('token');
        $pollId = $request->query('poll_id');
        $chatId = $request->query('chat_id');
        $candidateId = $request->query('candidate_id');

        if (!$token || !$pollId || !$chatId) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        // Verify token (simple hash verification)
        $expectedToken = md5($pollId . '_' . $chatId . '_' . config('app.key'));
        if ($token !== $expectedToken) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        return view('ip-collector', [
            'poll_id' => $pollId,
            'chat_id' => $chatId,
            'token' => $token,
            'candidate_id' => $candidateId
        ]);
    }

    /**
     * Collect IP address and verify ReCaptcha
     */
    public function collect(Request $request)
    {
        try {
            $pollId = $request->input('poll_id');
            $chatId = $request->input('chat_id');
            $token = $request->input('token');
            $captchaAnswer = $request->input('captcha_answer');
            $captchaCorrect = $request->input('captcha_correct');

            if (!$token || !$pollId || !$chatId) {
                return response()->json(['success' => false, 'message' => 'Invalid parameters'], 400);
            }

            // Verify token
            $expectedToken = md5($pollId . '_' . $chatId . '_' . config('app.key'));
            if ($token !== $expectedToken) {
                return response()->json(['success' => false, 'message' => 'Invalid token'], 403);
            }

            // Verify captcha answer (if provided)
            if ($captchaAnswer && $captchaCorrect) {
                if ((int)$captchaAnswer !== (int)$captchaCorrect) {
                    Log::warning('Captcha verification failed', [
                        'poll_id' => $pollId,
                        'chat_id' => $chatId,
                        'user_answer' => $captchaAnswer,
                        'correct_answer' => $captchaCorrect
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Captcha verification failed'
                    ], 400);
                }
            }

            // Get real IP address
            $realIp = $this->getRealIpAddress($request);

            Log::info('IP Collection with ReCaptcha', [
                'poll_id' => $pollId,
                'chat_id' => $chatId,
                'real_ip' => $realIp,
                'captcha_verified' => ($captchaAnswer == $captchaCorrect),
                'user_agent' => $request->header('User-Agent')
            ]);

            // Find participant
            $participant = PollParticipant::where('poll_id', $pollId)
                ->where('chat_id', $chatId)
                ->first();

            if (!$participant) {
                return response()->json(['success' => false, 'message' => 'Participant not found'], 404);
            }

            // Update IP address and mark both IP and ReCaptcha as verified
            $updateData = [
                'ip_address' => $realIp,
                'ip_verified' => true,
            ];

            // If captcha was checked, mark it as verified
            if ($captchaAnswer && $captchaCorrect) {
                $updateData['recaptcha_verified'] = true;
            }

            $participant->update($updateData);

            Log::info('IP and ReCaptcha verified successfully', [
                'poll_id' => $pollId,
                'chat_id' => $chatId,
                'ip' => $realIp,
                'recaptcha_verified' => isset($updateData['recaptcha_verified'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification successful',
                'ip' => $realIp
            ]);

        } catch (\Exception $e) {
            Log::error('IP Collection Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error during verification'
            ], 500);
        }
    }

    /**
     * Get real IP address from request
     */
    protected function getRealIpAddress(Request $request): string
    {
        // Check for IP in various headers (common proxy headers)
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',             // Nginx proxy
            'HTTP_X_FORWARDED_FOR',       // Standard forwarded header
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            $ip = $request->server($header);

            if ($ip) {
                // X-Forwarded-For can contain multiple IPs, take the first one
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback to remote address
        return $request->ip();
    }
}
