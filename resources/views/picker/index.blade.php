<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Manager — Picker</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; height: 100vh; display: flex; flex-direction: column; }

        /* Toolbar */
        .toolbar { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fff; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
        .toolbar h1 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .toolbar .spacer { flex: 1; }
        .search-box { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; width: 220px; outline: none; }
        .search-box:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.15); }
        .btn { padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: background .15s; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-outline { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .btn-outline:hover { background: #f9fafb; }

        /* Layout */
        .layout { display: flex; flex: 1; overflow: hidden; }

        /* Sidebar */
        .sidebar { width: 220px; background: #fff; border-right: 1px solid #e2e8f0; overflow-y: auto; flex-shrink: 0; padding: 12px 0; }
        .sidebar-title { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; padding: 0 16px 8px; }
        .folder-item { display: flex; align-items: center; gap: 8px; padding: 7px 16px; font-size: 13px; cursor: pointer; border-radius: 0; transition: background .1s; }
        .folder-item:hover { background: #f1f5f9; }
        .folder-item.active { background: #eff6ff; color: #2563eb; font-weight: 500; }
        .folder-item svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* Main */
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .breadcrumb { display: flex; align-items: center; gap: 4px; padding: 10px 16px; font-size: 13px; color: #64748b; border-bottom: 1px solid #f1f5f9; background: #fff; }
        .breadcrumb span { color: #3b82f6; cursor: pointer; }
        .breadcrumb span:hover { text-decoration: underline; }

        /* Filter bar */
        .filter-bar { display: flex; align-items: center; gap: 8px; padding: 10px 16px; background: #fff; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .filter-tag { padding: 3px 10px; border-radius: 100px; border: 1px solid #e2e8f0; cursor: pointer; font-size: 12px; color: #64748b; }
        .filter-tag.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }

        /* File grid */
        .file-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; padding: 16px; overflow-y: auto; flex: 1; align-content: start; }
        .file-card { display: flex; flex-direction: column; align-items: center; padding: 12px 8px 10px; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: all .15s; text-align: center; }
        .file-card:hover { background: #f8fafc; border-color: #e2e8f0; }
        .file-card.selected { background: #eff6ff; border-color: #93c5fd; }
        .file-icon { width: 56px; height: 56px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 8px; background: #f1f5f9; }
        .file-icon img { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; }
        .file-name { font-size: 12px; color: #1e293b; word-break: break-word; line-height: 1.4; max-width: 100%; }
        .file-meta { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        /* States */
        .state-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; color: #94a3b8; gap: 8px; padding: 40px; text-align: center; }
        .state-empty svg { width: 48px; height: 48px; opacity: .4; }
        .state-loading { display: flex; align-items: center; justify-content: center; flex: 1; }
        .spinner { width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: #3b82f6; border-radius: 50%; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Footer bar */
        .footer-bar { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fff; border-top: 1px solid #e2e8f0; flex-shrink: 0; }
        .footer-bar .info { font-size: 12px; color: #64748b; flex: 1; }
        .btn-select { padding: 8px 20px; border-radius: 6px; background: #22c55e; color: #fff; border: none; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-select:disabled { opacity: .4; cursor: not-allowed; }
        .btn-select:not(:disabled):hover { background: #16a34a; }
    </style>
</head>
<body>
    <div class="toolbar">
        <svg width="20" height="20" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
        <h1>File Manager</h1>
        <span class="spacer"></span>
        <input class="search-box" type="search" id="searchInput" placeholder="Search files…">
        <button class="btn btn-outline" id="btnUpload">↑ Upload</button>
    </div>

    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-title">Folders</div>
            <div id="folderTree">
                <div class="folder-item active" data-id="">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    All Files
                </div>
            </div>
        </aside>

        <main class="main">
            <div class="breadcrumb" id="breadcrumb">
                <span data-id="">🏠 Root</span>
            </div>

            <div class="filter-bar" id="filterBar">
                <span class="filter-tag active" data-type="">All</span>
                @if(in_array($type, ['image', 'file']))
                <span class="filter-tag" data-type="image/">Images</span>
                @endif
                @if($type === 'file')
                <span class="filter-tag" data-type="application/pdf">PDF</span>
                <span class="filter-tag" data-type="application/">Documents</span>
                @endif
                @if($type === 'video')
                <span class="filter-tag" data-type="video/">Videos</span>
                @endif
            </div>

            <div class="file-grid" id="fileGrid">
                <div class="state-loading" style="grid-column:1/-1">
                    <div class="spinner"></div>
                </div>
            </div>

            <div class="footer-bar">
                <span class="info" id="footerInfo">Loading…</span>
                <button class="btn btn-outline" onclick="window.close()">Cancel</button>
                <button class="btn-select" id="btnSelect" disabled>Insert Selected</button>
            </div>
        </main>
    </div>

    <script>
    (function () {
        const CALLBACK   = @json($callback);
        const TYPE       = @json($type);
        const API_BASE   = @json($apiBase);
        const PICKER_URL = '{{ route("filemanager.picker.select", ["fileId" => "__ID__"]) }}';

        let currentFolderId = null;
        let selectedFile = null;
        let mimeFilter = '';

        const fileGrid   = document.getElementById('fileGrid');
        const footerInfo = document.getElementById('footerInfo');
        const btnSelect  = document.getElementById('btnSelect');

        const MIME_ICONS = {
            'image': '🖼️', 'application/pdf': '📄', 'video': '🎬',
            'audio': '🎵', 'application/zip': '🗜️', 'text': '📝', 'default': '📁'
        };

        function getMimeIcon(mime) {
            for (const [k, v] of Object.entries(MIME_ICONS)) {
                if (mime.startsWith(k)) return v;
            }
            return MIME_ICONS.default;
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
            return (bytes/1048576).toFixed(1) + ' MB';
        }

        async function loadFiles(folderId, mime) {
            fileGrid.innerHTML = '<div class="state-loading" style="grid-column:1/-1"><div class="spinner"></div></div>';

            const params = new URLSearchParams({ per_page: 50 });
            if (folderId) params.set('folder_id', folderId);
            if (mime)     params.set('mime_type', mime);

            try {
                const res  = await fetch(`${API_BASE}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                renderFiles(json.data || []);
                footerInfo.textContent = `${(json.meta?.total || json.data?.length || 0)} file(s)`;
            } catch (e) {
                fileGrid.innerHTML = '<div class="state-empty" style="grid-column:1/-1"><p>⚠️ Failed to load files.</p></div>';
            }
        }

        function renderFiles(files) {
            if (!files.length) {
                fileGrid.innerHTML = `<div class="state-empty" style="grid-column:1/-1">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    <span>No files found</span>
                </div>`;
                return;
            }

            fileGrid.innerHTML = '';
            files.forEach(file => {
                const card = document.createElement('div');
                card.className = 'file-card';
                card.dataset.id = file.id;

                const icon = file.mime_type.startsWith('image/') && file.thumbnail_url
                    ? `<img src="${file.thumbnail_url}" alt="${file.name}" loading="lazy">`
                    : `<span>${getMimeIcon(file.mime_type)}</span>`;

                card.innerHTML = `
                    <div class="file-icon">${icon}</div>
                    <div class="file-name">${file.name}</div>
                    <div class="file-meta">${formatSize(file.size)}</div>
                `;

                card.addEventListener('click', () => selectFile(card, file));
                card.addEventListener('dblclick', () => insertFile(file));
                fileGrid.appendChild(card);
            });
        }

        function selectFile(card, file) {
            document.querySelectorAll('.file-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedFile = file;
            btnSelect.disabled = false;
            footerInfo.textContent = `Selected: ${file.name} (${formatSize(file.size)})`;
        }

        function insertFile(file) {
            const url = PICKER_URL.replace('__ID__', file.id) + `?callback=${encodeURIComponent(CALLBACK)}`;
            window.location.href = url;
        }

        btnSelect.addEventListener('click', () => {
            if (selectedFile) insertFile(selectedFile);
        });

        // Filter tags
        document.getElementById('filterBar').addEventListener('click', e => {
            const tag = e.target.closest('[data-type]');
            if (!tag) return;
            document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
            tag.classList.add('active');
            mimeFilter = tag.dataset.type;
            loadFiles(currentFolderId, mimeFilter);
        });

        // Search
        let searchTimer;
        document.getElementById('searchInput').addEventListener('input', e => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadFiles(currentFolderId, mimeFilter), 400);
        });

        // Upload
        document.getElementById('btnUpload').addEventListener('click', () => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = TYPE === 'image' ? 'image/*' : '*/*';
            input.onchange = async () => {
                if (!input.files[0]) return;
                const form = new FormData();
                form.append('file', input.files[0]);
                if (currentFolderId) form.append('folder_id', currentFolderId);

                const res = await fetch('/api/v1/filemanager/upload', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: form
                });
                if (res.ok) loadFiles(currentFolderId, mimeFilter);
            };
            input.click();
        });

        // Initial load
        loadFiles(null, '');
    })();
    </script>
</body>
</html>
