<!doctype html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>ILESS Examples</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $css; ?>">
    <style>
    pre {
      background: #ccc;
      padding: 0.5em;
      overflow: auto;
    }
    </style>
</head>
  <body>
    <div id="header">
      <h1>
        ILess examples
<?php if(isset($example)): ?>
        &dash; <?php echo $example; ?>
<?php endif; ?>
      </h1>
    </div>
    <pre><?php echo $cssContent; ?></pre>
  </body>
</html>