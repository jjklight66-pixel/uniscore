<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniScore Mobile App REST API v1 — Swagger Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; font-family: system-ui, -apple-system, sans-serif; }
        .topbar { display: none; }
        .custom-header {
            background: #0f172a;
            color: #fff;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #22c55e;
        }
        .custom-header h1 { margin: 0; font-size: 18px; font-weight: 700; }
        .custom-header a { color: #86efac; text-decoration: none; font-size: 14px; margin-left: 16px; }
        .custom-header a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="custom-header">
        <h1>⚽ UniScore Mobile App REST API v1</h1>
        <div>
            <a href="/openapi.json" target="_blank">OpenAPI JSON (Import Postman)</a>
            <a href="/">← Trang chủ Web</a>
        </div>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function () {
            SwaggerUIBundle({
                url: '/openapi.json',
                dom_id: '#swagger-ui',
                deepLinking: true,
                persistAuthorization: true,
                presets: [SwaggerUIBundle.presets.apis]
            });
        };
    </script>
</body>
</html>
