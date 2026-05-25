{{-- resources/views/share/show.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download: {{ $share->file->name }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); max-width: 420px; width: 100%; text-align: center; }
        .icon { font-size: 56px; margin-bottom: 16px; }
        h1 { font-size: 18px; color: #0f172a; margin-bottom: 8px; word-break: break-word; }
        .meta { color: #64748b; font-size: 13px; margin-bottom: 24px; }
        .btn { display: inline-block; padding: 12px 28px; background: #3b82f6; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px; }
        .btn:hover { background: #2563eb; }
        .note { margin-top: 16px; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📥</div>
        <h1>{{ $share->file->name }}</h1>
        <div class="meta">
            {{ $share->file->size_human }} · {{ strtoupper($share->file->extension) }}
            @if($share->expires_at)
            · Expires {{ $share->expires_at->diffForHumans() }}
            @endif
        </div>
        <a href="{{ route('filemanager.share.download', $share->token) }}" class="btn">Download File</a>
        <p class="note">
            @if($share->max_downloads)
            {{ $share->max_downloads - $share->download_count }} download(s) remaining
            @endif
        </p>
    </div>
</body>
</html>
