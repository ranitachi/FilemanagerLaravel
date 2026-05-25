<!DOCTYPE html>
<html>
<head>
    <title>Selecting file…</title>
</head>
<body>
<script>
(function () {
    'use strict';

    var fileUrl  = @json($fileUrl);
    var fileName = @json($fileName);
    var mimeType = @json($mimeType);
    var callback = @json($callback);

    // Validate callback is a valid identifier (server already validated, double-check client)
    if (!/^[a-zA-Z_$][a-zA-Z0-9_$.]*$/.test(callback)) {
        document.write('<p>Error: Invalid callback name.</p>');
        return;
    }

    if (window.opener && typeof window.opener[callback] === 'function') {
        try {
            window.opener[callback](fileUrl, fileName, { mimeType: mimeType });
        } catch (e) {
            console.error('FileManager callback error:', e);
        }
        window.close();
    } else {
        document.write('<p style="font-family:sans-serif;padding:20px;color:#666">File selected. You may close this window.</p>');
        setTimeout(function () { window.close(); }, 2000);
    }
})();
</script>
</body>
</html>
