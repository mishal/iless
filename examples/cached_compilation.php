<?php

require_once '_bootstrap.php';

// create the parser
$parser = new ILess_Parser(array(), new ILess_Cache_FileSystem(array(
    'cache_dir' => dirname(__FILE__) . '/cache'
)));

$file = dirname(__FILE__) . '/less/test.less';
// create your cache key
$cacheKey = md5($file);
$importer = $parser->getImporter();
$cache = $parser->getCache();

$rebuild = true;
$cssLastModified = -1;

if ($cache->has($cacheKey)) {
    $rebuild = false;
    list($css, $importedFiles) = $cache->get($cacheKey);
    // we need to check if the file has been modified
    foreach ($importedFiles as $importedFileArray) {
        list($lastModifiedBefore, $path, $currentFileInfo) = $importedFileArray;
        $lastModified = $importer->getLastModified($path, $currentFileInfo);
        $cssLastModified = max($lastModified, $cssLastModified);
        if ($lastModifiedBefore != $lastModified) {
            $rebuild = true;
            // no need to continue, we will rebuild the CSS
            break;
        }
    }
}

if ($rebuild) {
    $parser->parseFile($file);

    $css = $parser->getCSS();
    // what have been imported?
    $importedFiles = array();
    foreach ($importer->getImportedFiles() as $importedFile) {
        $importedFiles[] = array($importedFile[0]->getLastModified(), $importedFile[1], $importedFile[2]);
        $cssLastModified = max($cssLastModified, $importedFile[0]->getLastModified());
    }

    $cache->set($cacheKey, array($css, $importedFiles));
}

header('Content-Type: text/css');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $cssLastModified) . 'GMT');

echo $css;
