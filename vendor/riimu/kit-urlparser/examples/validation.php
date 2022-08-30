<?php

require_once __DIR__ . '/../vendor/autoload.php';

$getValidUrl = function ($url) {
    $parser = new \Riimu\Kit\UrlParser\UriParser();
    $parser->setMode(\Riimu\Kit\UrlParser\UriParser::MODE_UTF8);
    $uri = $parser->parse($url);

    if ($uri === null) {
        return false;
    } elseif (!in_array($uri->getScheme(), ['http', 'https'], true)) {
        return false;
    } elseif ($uri->getTopLevelDomain() === $uri->getHost()) {
        return false;
    }

    return (string) $uri;
};

$normalized = null;

if (isset($_POST['url'])) {
    $normalized = $getValidUrl($_POST['url']);
}

?>
<!DOCTYPE html>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Validate an URL</title>
 </head>
 <body>
<?php

if ($normalized === false) {
    printf(
        "  <p>The URL '<code>%s</code>' is <b>not valid</b></p>" . PHP_EOL,
        htmlspecialchars($_POST['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
    );
} elseif ($normalized !== null) {
    printf(
        "  <p>The URL '<code>%s</code>' is valid!</p>" . PHP_EOL,
        htmlspecialchars($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8')
    );
}

?>
  <h3>Validate an URL:</h3>
  <form method="post"><div>
   URL
   <input type="text" name="url" />
   <input type="submit" />
  </div></form>
 </body>
</html>
