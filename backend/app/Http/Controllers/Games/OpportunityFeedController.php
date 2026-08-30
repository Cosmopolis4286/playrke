<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Services\Games\OpportunityFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OpportunityFeedController extends Controller
{
    public function __construct(
        private readonly OpportunityFeedService $feed,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Games/Opportunities/Index', [
            'feed' => $this->feed->getFeed((int) Auth::id()),
        ]);
    }

    /**
     * Refetch JSON — para el botón de refrescar sin recargar la página.
     */
    public function fetch(): JsonResponse
    {
        return response()->json($this->feed->getFeed((int) Auth::id()));
    }
}
