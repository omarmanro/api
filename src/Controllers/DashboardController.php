<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DashboardService;

class DashboardController extends BaseController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    public function index(): array
    {
        $plantelId = $this->getPlantelId();
        $data = $this->dashboardService->getDashboardData($plantelId);

        return $this->success($data);
    }

    public function summary(): array
    {
        $plantelId = $this->getPlantelId();
        $data = $this->dashboardService->getDashboardData($plantelId);

        return $this->success($data['summary']);
    }

    public function charts(): array
    {
        $plantelId = $this->getPlantelId();
        $data = $this->dashboardService->getDashboardData($plantelId);

        return $this->success($data['charts']);
    }

    public function alerts(): array
    {
        $plantelId = $this->getPlantelId();
        $data = $this->dashboardService->getDashboardData($plantelId);

        return $this->success($data['alerts']);
    }
}
