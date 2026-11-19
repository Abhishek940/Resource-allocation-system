<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

class DashboardContrller extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function getDashboardcount(Request $request): JsonResponse
    {
      
      $data = $this->dashboardService->getDashboardCounts();

        return response()->json([
            'status'=> true,
            'message' => 'Dashboard Counts retrieved successfully',
            'data' => $data,
      
        ],200);

   }

   public function getMatchingResources() {

   try {

      $data = $this->dashboardService->getMatchingResources();
      return response()->json([
           'success'=>true,
           'message'=>'Matching Resources Retrive successfully',
           'data'=>$data
      ],200);

   } catch(\Throwable $e){
         return response()->json([
          'success'=>false,
          'maeesage'=>'Unable to retrive matching resources',
          'error'=>$e->getMessage()

         ],500);
    
    }

    }

    public function getstrongMatchCandidates()
    {
      
        try {

        $data = $this->dashboardService->getStrongMatchCandidates();
       return response()->json([
                'sucess'=>true,
                'message'=>'Strong Match Candidates retrive Successfully',
                'data'=>$data
        ],200);

        dd(3);
        } catch(\Throwable $e){
            return response()->json([
                'success'=>false,
                'message'=>'unable to retrive strong-match candidates'
            ],500);
        }
    }


public function getUpskillingCandidates()
{
    try {

        $result = $this->dashboardService->getUpskillingCandidates();

        return response()->json([
            'success' => true,
            'message' => 'Upskilling candidates retrieved successfully.',
            'data' => $result
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Unable to retrieve upskilling candidates.',
            'error' => $e->getMessage()
        ], 500);
    }
}


}
