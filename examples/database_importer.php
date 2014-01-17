<?php

require_once '_bootstrap.php';

$pdo = new PDO('sqlite::memory:');

$statements = array(
  'CREATE TABLE [less] (
    [filename] VARCHAR(255),
    [data] LONGVARCHAR,
    [updated_at] TIMESTAMP
  )',
  'CREATE UNIQUE INDEX [filename_idx] ON [less] ([filename])',
);

foreach($statements as $statement)
{
  if(!$pdo->query($statement))
  {
    $error = $pdo->errorInfo();
    throw new Exception($error[2], $error[1]);
  }
}

$stmt = $pdo->prepare('INSERT INTO less(filename, data, updated_at) VALUES(?, ?, ?)');

foreach(array(
        array('foo.less', 'body { background: @color; }', time()),
        array('mixins.less', '.mixin(@a) { background: @a; }', time())
        ) as $line)
{
  $result = $stmt->execute(array(
    $line[0], $line[1], $line[2]
  ));
}

try {
  $cacheDir = dirname(__FILE__) . '/cache';

  $parser = new ILess_Parser();
  $parser->getImporter()->registerImporter(new ILess_Importer_Database($pdo, array(
    'table_name' => 'less',
    'filename_column' => 'filename',
    'data_column' => 'data',
    'updated_at_column' => 'data',
  )));

  $parser->parseString('

  @color: red;

  @import url("foo.less");
  @import (reference) url("mixins.less");

  #head {
    color: @color + #fff;
    .mixin(yellow);
  }

  ');

  $cssContent = $parser->getCSS();
  file_put_contents($cacheDir . '/database.css', $cssContent);
  $css = 'cache/database.css';
}
catch(Exception $e)
{
  @header('HTTP/1.0 500 Internal Server Error');
  echo $e;
  exit;
}

$example = 'database importer';
include '_page.php';
