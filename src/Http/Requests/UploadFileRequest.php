<?php

namespace Fachran\FileManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $maxSizeKb  = config('filemanager.max_upload_size_kb', 10240);
        $extensions = implode(',', config('filemanager.allowed_extensions', []));

        return [
            'file'      => [
                'required',
                'file',
                "max:{$maxSizeKb}",
                "mimes:{$extensions}",
            ],
            'folder_id' => ['nullable', 'string', 'exists:fm_folders,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFile('file') || ! $this->file('file')->isValid()) {
                return;
            }

            $file   = $this->file('file');
            $handle = fopen($file->getRealPath(), 'rb');
            $bytes  = fread($handle, 8);
            fclose($handle);

            $phpSignatures = ['<?php', '<?=', '<? ', '#!/'];
            foreach ($phpSignatures as $sig) {
                if (str_starts_with($bytes, $sig)) {
                    $validator->errors()->add('file', 'File contains executable code and is not allowed.');
                    return;
                }
            }
        });
    }
}
