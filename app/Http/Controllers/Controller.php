<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

     /**
     * Format and send back response to client
     *
     * @return Illuminate\Http\JsonResponse 
     */
    public function respond($data = null, $code = 200, $message = 'Success')
    {

    	$response = [
    		'status_code' => $code,
    		'message' => $message
    	];

        if (isset($data)) {
            $response['data'] = $data;
        }

        if ($data instanceof Exception) {
            Log::emergency("An exception occurred: {$data->getMessage()}", compact('data'));

            unset($response['data']);
            $response['status_code'] = 500;
            $response['message'] = "Failed. An error occurred while processing your request";

            if ($data instanceof ValidationException) {
                $response['data'] = $data->errors();
                $response['status_code'] = 400;
                $response['message'] = $data->getMessage();
            }
        }

        if ($data instanceof ResourceCollection) {
            return $data->additional(
                    Arr::only($response, [ 'status_code', 'message', 'meta' ])
                )
                ->response()
                ->setStatusCode($code);
        }

    	return response()->json($response, $response['status_code']);
    }
}
