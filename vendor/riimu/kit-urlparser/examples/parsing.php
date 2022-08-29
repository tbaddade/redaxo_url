<?php

require_once __DIR__ . '/../vendor/autoload.php';

$parser = new \Riimu\Kit\UrlParser\UriParser();
$uri = $parser->parse('http://jane:pass123@www.example.com:8080/site/index.php?action=login&prev=index#form');

echo $uri->getScheme() . PHP_EOL;         // outputs: http
echo $uri->getUsername() . PHP_EOL;       // outputs: jane
echo $uri->getPassword() . PHP_EOL;       // outputs: pass123
echo $uri->getHost() . PHP_EOL;           // outputs: www.example.com
echo $uri->getTopLevelDomain() . PHP_EOL; // outputs: com
echo $uri->getPort() . PHP_EOL;           // outputs: 8080
echo $uri->getStandardPort() . PHP_EOL;   // outputs: 80
echo $uri->getPath() . PHP_EOL;           // outputs: /site/index.php
echo $uri->getPathExtension() . PHP_EOL;  // outputs: php
echo $uri->getQuery() . PHP_EOL;          // outputs: action=login&prev=index
echo $uri->getFragment() . PHP_EOL;       // outputs: form

// [0 => 'site', 1 => 'index.php']
echo implode(', ', $uri->getPathSegments()) . PHP_EOL;

// ['action' => 'login', 'prev' => 'index']
foreach ($uri->getQueryParameters() as $name => $value) {
    echo "$name: $value" . PHP_EOL;
}

// Outputs: http://jane:pass123@www.example.com:8080/site/index.php?action=login&prev=index#form
echo $uri;
