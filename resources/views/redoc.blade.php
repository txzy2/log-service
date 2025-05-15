<!DOCTYPE html>
<html>
<head>
    <title>API Documentation</title>
    <script src="https://cdn.jsdelivr.net/npm/redoc@next/bundles/redoc.standalone.js"></script>
</head>
<body>
    <div id="redoc-container"></div>
    <script>
        Redoc.init('/docs/openapi.json', {}, document.getElementById('redoc-container'));
    </script>
</body>
</html>
