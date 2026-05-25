<?php

namespace Fachran\FileManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fachran\FileManager\Services\ShareService;

class ShareController extends Controller
{
    public function __construct(protected ShareService $shareService) {}

    /** POST /files/{id}/share */
    public function store(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'expires_in_minutes' => ['nullable', 'integer', 'min:1', 'max:43200'],
            'max_downloads'      => ['nullable', 'integer', 'min:1'],
            'password'           => ['nullable', 'string', 'min:4'],
        ]);

        $share = $this->shareService->create(
            fileId:           $fileId,
            expiresInMinutes: $request->integer('expires_in_minutes', 1440),
            maxDownloads:     $request->integer('max_downloads') ?: null,
            password:         $request->password,
        );

        return response()->json([
            'message' => 'Share link created.',
            'data' => [
                'share_url'    => $share->getShareUrl(),
                'token'        => $share->token,
                'expires_at'   => $share->expires_at,
                'max_downloads'=> $share->max_downloads,
                'has_password' => $share->hasPassword(),
            ],
        ], 201);
    }

    /** GET /share/{token} — public share page */
    public function show(string $token)
    {
        try {
            $share = $this->shareService->resolve($token);
        } catch (\Fachran\FileManager\Exceptions\ShareExpiredException) {
            return view('filemanager::share.expired');
        } catch (\Fachran\FileManager\Exceptions\ShareLimitReachedException) {
            return view('filemanager::share.limit_reached');
        } catch (\Fachran\FileManager\Exceptions\ShareNotFoundException) {
            abort(404);
        }

        if ($share->hasPassword()) {
            return view('filemanager::share.password', compact('token'));
        }

        return view('filemanager::share.show', compact('share'));
    }

    /** GET /share/{token}/download */
    public function download(Request $request, string $token)
    {
        return $this->shareService->download($token, $request->password);
    }

    /** DELETE /share/{token} */
    public function revoke(string $token): JsonResponse
    {
        $this->shareService->revoke($token);
        return response()->json(['message' => 'Share link revoked.']);
    }
}
