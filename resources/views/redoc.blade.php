<!DOCTYPE html>
<html>

<head>
    <title>API Documentation</title>
    <script src="https://cdn.jsdelivr.net/npm/redoc@latest/bundles/redoc.standalone.js"></script>
</head>

<body>
    <div id="redoc-container"></div>
    <script>
        Redoc.init('{{ url('/docs/openapi.yaml') }}', {}, document.getElementById('redoc-container'));
    </script>
</body>

</html>