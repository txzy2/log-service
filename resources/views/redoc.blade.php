<!DOCTYPE html>
<html>
  <head>
    <title>API Docs</title>
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
  </head>
  <body>
    <div id="redoc-container"></div>
    <script>
      Redoc.init(
        "{{ asset('openapi.yaml') }}",
        {},
        document.getElementById('redoc-container')
      );
    </script>
  </body>
</html>
