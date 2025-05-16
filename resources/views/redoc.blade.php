<!DOCTYPE html>
<html>
  <head>
    <title>API Docs</title>
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
  </head>
  <body>
    <div id="redoc-container"></div>

    <script>
      const specUrl = window.location.protocol + "//" +
                     window.location.host + "/openapi.yaml";

      Redoc.init(specUrl, {}, document.getElementById('redoc-container'));
    </script>
  </body>
</html>
