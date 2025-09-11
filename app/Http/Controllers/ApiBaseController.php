<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiBaseController extends Controller
{
    public function sendResponse($result, $message, $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => $message
        ], $code);
    }

    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
