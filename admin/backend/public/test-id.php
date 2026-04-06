<?php
// Identity Test - Visit this at api.amudaryoavtotest.uz/test-id.php
echo "<h3>Server Identity Test</h3>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "<br>";
echo "REQUEST_SCHEME: " . ($_SERVER['REQUEST_SCHEME'] ?? 'NOT SET') . "<br>";
echo "<hr>";
echo "<strong>Laravel Environment Check:</strong><br>";
echo "APP_URL in memory: " . (function_exists('env') ? env('APP_URL') : 'N/A') . "<br>";
echo "Config URL: " . (function_exists('config') ? config('app.url') : 'N/A') . "<br>";
echo "<hr>";
echo "Laravel URL Generator: " . (function_exists('url') ? url('/') : 'Laravel not loaded');
