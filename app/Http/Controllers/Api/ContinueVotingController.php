<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollCandidate;
use App\Models\PollParticipant;
use App\Services\PollBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContinueVotingController extends Controller
{
    protected PollBotService $pollBotService;

    public function __construct(PollBotService $pollBotService)
    {
        $this->pollBotService = $pollBotService;
    }

    /**
     * Continue voting flow after WebApp verification
     */
    public function continueVoting(Request $request)
    {
        try {
            $pollId = $request->input('poll_id');
            $chatId = $request->input('chat_id');
            $candidateId = $request->input('candidate_id');
            $token = $request->input('token');

            if (!$token || !$pollId || !$chatId) {
                return response()->json(['success' => false, 'message' => 'Invalid parameters'], 400);
            }

            // Verify token
            $expectedToken = md5($pollId . '_' . $chatId . '_' . config('app.key'));
            if ($token !== $expectedToken) {
                return response()->json(['success' => false, 'message' => 'Invalid token'], 403);
            }

            Log::info('Continue voting after WebApp verification', [
                'poll_id' => $pollId,
                'chat_id' => $chatId,
                'candidate_id' => $candidateId
            ]);

            // Find poll and participant
            $poll = Poll::find($pollId);
            $participant = PollParticipant::where('poll_id', $pollId)
                ->where('chat_id', $chatId)
                ->first();

            if (!$poll || !$participant) {
                return response()->json(['success' => false, 'message' => 'Poll or participant not found'], 404);
            }

            // Verify that participant completed verification
            if (!$participant->ip_verified) {
                return response()->json(['success' => false, 'message' => 'Verification not complete'], 400);
            }

            // Continue with voting flow
            if ($candidateId) {
                // User has preselected a candidate - show vote confirmation
                $candidate = PollCandidate::find($candidateId);
                if ($candidate && $candidate->poll_id == $poll->id) {
                    $this->pollBotService->sendVoteConfirmation($chatId, $poll, $candidate);

                    Log::info('Vote confirmation sent', [
                        'poll_id' => $pollId,
                        'chat_id' => $chatId,
                        'candidate_id' => $candidateId
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Vote confirmation sent'
                    ]);
                }
            }

            // No preselected candidate - show all candidates
            $this->pollBotService->sendCandidatesList($chatId, $poll);

            return response()->json([
                'success' => true,
                'message' => 'Candidates list sent'
            ]);

        } catch (\Exception $e) {
            Log::error('Continue voting error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error continuing voting flow'
            ], 500);
        }
    }
}
