<?php declare (strict_types = 1);

namespace App\Http\Controllers;

use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Handle a lookup request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'     => 'required|string',
            'username' => 'nullable|string',
            'id'       => 'nullable|string',
        ]);

        $username = $data['username'] ?? null;
        $userId   = $data['id'] ?? null;

        if (empty($username) && empty($userId)) {
            return response()->json(
                [
                    'success' => false,
                    'error'   => 'Username or ID required',
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $result = $this->lookupService->lookup($data['type'], [
                'username' => $username,
                'id'       => $userId,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(
                [
                    'success'   => false,
                    'error'     => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($result === null) {
            return response()->json(
                [
                    'success'   => false,
                    'error'     => 'No matching records were found for the input provided.',
                    'timestamp' => now()->toIso8601String(),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        if (isset($result['success']) && $result['success'] === false && isset($result['error'])) {
            return response()->json(
                [
                    'success'   => false,
                    'error'     => $result['error'],
                    'timestamp' => now()->toIso8601String(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return response()->json(array_merge(['success' => true], $result), Response::HTTP_OK);
    }
}
