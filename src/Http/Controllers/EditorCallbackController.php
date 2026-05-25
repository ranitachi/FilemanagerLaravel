<?php

namespace Fachran\FileManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fachran\FileManager\Services\FileService;
use Fachran\FileManager\Services\PermissionService;

class EditorCallbackController extends Controller
{
    public function __construct(
        protected FileService $fileService,
        protected PermissionService $permissionService,
    ) {}

    /**
     * Open File Manager in picker mode (used by WYSIWYG editors).
     *
     * Usage:
     *   /filemanager/picker?callback=MyEditorCallback&type=image
     */
    public function picker(Request $request)
    {
        $request->validate([
            'callback' => [
                'required',
                'string',
                // Only allow valid JS identifier characters to prevent XSS
                'regex:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*$/',
                'max:100',
            ],
            'type' => ['nullable', 'in:image,file,video,audio'],
        ]);

        return view('filemanager::picker.index', [
            'callback' => $request->callback,
            'type'     => $request->type ?? 'file',
            'apiBase'  => route('filemanager.api.files.index'),
        ]);
    }

    /**
     * Called when user selects a file in picker mode.
     * Returns an HTML page with JS to call parent window callback.
     */
    public function select(Request $request, string $fileId)
    {
        $request->validate([
            'callback' => [
                'required',
                'string',
                'regex:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*$/',
                'max:100',
            ],
        ]);

        $file = $this->fileService->findOrFail($fileId);
        $user = auth()->user();

        abort_unless(
            $this->permissionService->can($user, $file, 'read'),
            403,
            'You do not have permission to use this file.'
        );

        $fileUrl  = route('filemanager.files.download', $file->id);
        $fileName = $file->name;
        $mimeType = $file->mime_type;
        $callback = $request->callback;

        return view('filemanager::picker.callback', compact(
            'fileUrl', 'fileName', 'mimeType', 'callback'
        ));
    }
}
