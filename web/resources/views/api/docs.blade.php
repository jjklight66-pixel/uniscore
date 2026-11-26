<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniScore Mobile App REST API v1 — Tài liệu Swagger</title>
    @include('partials.fl-head')
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        .topbar { display: none; }
        #swagger-ui,
        #swagger-ui .swagger-ui,
        #swagger-ui .swagger-ui .info .title,
        #swagger-ui .swagger-ui .opblock-tag,
        #swagger-ui .swagger-ui .opblock .opblock-summary-description,
        #swagger-ui .swagger-ui .tab li,
        #swagger-ui .swagger-ui .btn,
        #swagger-ui .swagger-ui select,
        #swagger-ui .swagger-ui input,
        #swagger-ui .swagger-ui textarea,
        #swagger-ui .swagger-ui table thead tr th,
        #swagger-ui .swagger-ui table tbody tr td,
        #swagger-ui .swagger-ui .response-col_status,
        #swagger-ui .swagger-ui .markdown p,
        #swagger-ui .swagger-ui .renderedMarkdown p {
            font-family: var(--font-body);
        }
        #swagger-ui .swagger-ui code,
        #swagger-ui .swagger-ui pre,
        #swagger-ui .swagger-ui .microlight,
        #swagger-ui .swagger-ui .opblock .opblock-summary-path {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        #swagger-ui .swagger-ui .info .title {
            font-family: var(--font-score);
            font-weight: 700;
            color: var(--ink);
            letter-spacing: 0.01em;
        }
        #swagger-ui .swagger-ui .info { margin: 20px 0; }
        #swagger-ui .swagger-ui .scheme-container {
            background: var(--flood-2);
            box-shadow: none;
            border-top: 1px solid var(--chalk);
            border-bottom: 1px solid var(--chalk);
            padding: 16px 0;
        }
        #swagger-ui .swagger-ui .opblock-tag {
            color: var(--ink);
            border-bottom-color: var(--chalk);
        }
        #swagger-ui .swagger-ui .btn.execute {
            background-color: var(--amber);
            border-color: var(--amber);
            color: #1A1200;
            font-weight: 600;
        }
        #swagger-ui .swagger-ui .btn.execute:hover {
            background-color: #FFC24D;
            border-color: #FFC24D;
        }
        #swagger-ui .swagger-ui .btn.authorize {
            color: var(--amber-ink);
            border-color: var(--amber);
        }
        #swagger-ui .swagger-ui .btn.authorize svg {
            fill: var(--amber-ink);
        }
        #swagger-ui .swagger-ui .wrapper {
            max-width: none;
            padding: 0;
        }
    </style>
</head>
<body class="fl min-h-screen flex flex-col">
    <header class="bg-stand border-b border-seat sticky top-0 z-40">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="/" class="font-score text-2xl font-extrabold tracking-tight leading-none select-none">
                    <span class="text-frost">UNI</span><span class="text-live">SCORE</span>
                </a>
                <span class="h-4 w-px bg-seat hidden sm:inline-block"></span>
                <h1 class="text-sm sm:text-base font-semibold text-frost">Mobile App REST API v1</h1>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="/openapi.json" target="_blank" class="fl-btn text-xs">OpenAPI JSON</a>
                <a href="/" class="fl-btn fl-btn--primary text-xs">Trang chủ</a>
            </div>
        </div>
    </header>

    <main class="max-w-[1440px] w-full mx-auto px-4 sm:px-6 py-5 flex-1">
        <div class="fl-field border border-chalk p-4 sm:p-6">
            <div id="swagger-ui"></div>
        </div>
    </main>

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
