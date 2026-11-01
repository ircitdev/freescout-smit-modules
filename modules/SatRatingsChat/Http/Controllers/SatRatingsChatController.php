<?php

namespace Modules\SatRatingsChat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SatRatingsChat\Providers\SatRatingsChatServiceProvider;

class SatRatingsChatController extends Controller
{
    /**
     * Handle VK rating callback from the Python bridge.
     * POST /satratingschat/vk-rate
     * Body: { "thread_id": 123, "rating": 1 }
     */
    public function vkRate(Request $request)
    {
        // мост должен прислать общий секрет (SATRATINGSCHAT_BRIDGE_SECRET в .env), без него — отказ
        $secret = (string) env('SATRATINGSCHAT_BRIDGE_SECRET', '');
        if ($secret === '' || !hash_equals($secret, (string) $request->header('X-Bridge-Secret', ''))) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }
        $threadId = (int) $request->input('thread_id');
        $rating = (int) $request->input('rating');

        if (!$threadId || $rating < 1 || $rating > 3) {
            return response()->json(['success' => false, 'error' => 'Invalid parameters'], 400);
        }

        $saved = SatRatingsChatServiceProvider::saveRating($threadId, $rating);

        return response()->json([
            'success' => $saved,
            'message' => $saved ? 'Rating saved' : 'Already rated or thread not found',
        ]);
    }
}
