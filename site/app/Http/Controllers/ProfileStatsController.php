<?php

namespace App\Http\Controllers;

use App\Services\PlayerStatsRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileStatsController extends Controller
{
    public function __construct(
        private PlayerStatsRepository $playerStats
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $stats = $this->playerStats->getStatsForUser($user);

        return Inertia::render('profile/Stats', $stats);
    }
}
