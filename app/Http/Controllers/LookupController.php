<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

/**
 * Class LookupController
 *
 * @package App\Http\Controllers
 */
class LookupController extends Controller
{
    private LookupService $lookupService;

    public function __construct(LookupService $lookupService)
    {
        $this->lookupService = $lookupService;
    }

    public function lookup(Request $request) : JsonResponse
    {
        $type = (string) $request->get('type', '');
        $username = $request->get('username', false);
        $userId   = $request->get('id', false);

        if (empty($type)) {
            return response()->json(['success' => false, 'error' => 'Type is required'], 422);
        }

        if (empty($username) && empty($userId)) {
            return response()->json(['success' => false, 'error' => 'Username or ID required'], 422);
        }

        try {
            $result = $this->lookupService->lookup($type, [
                'username' => $username,
                'id' => $userId,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([ 'success' => false, 'error' => $e->getMessage()], 422);
        }

         if ($result === null || isset($result['error'])) {
            return response()->json(
                [
                    'success' => false, 
                    'error' => $result === null ? 'No result found' : $result['error'], 
                    'timestamp' => now()->toIso8601String()
                ], $result === null ? 404 : 422
            );
        }

        return response()->json(['success' => true] + $result);
    }
}
