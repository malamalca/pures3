<?php
  use App\Core\App;
?>
<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">

    <title>PURES3 + HRUP13</title>
    <meta name="description" content="PHPures 3">
    <meta name="author" content="ARHIM d.o.o.">

    <script type="text/javascript" src="<?= App::url("/js/ASCIIMathML.js") ?>"></script>
    <link rel="stylesheet" href="<?= App::url("/css/main.css") ?>" />
    <link rel="stylesheet" media="print" href="<?= App::url("/css/print.css") ?>" />
</head>

<body translate="no">
  <div id="container">
      <?php require TEMPLATES . 'elements' . DS . 'header.php'; ?>
      <div id="content">
        <?= App::flash() ?>

        <?php
          if (!empty($sidebar)) {
        ?>
        <div id="sidebar">
        <?php require TEMPLATES . 'elements' . DS . 'sidebar' . DS . $sidebar . '.php'; ?>
        </div>
        <?php
          }
        ?>

        <div id="main"<?php if (isset($sidebar)) echo ' style="margin-left: 230px;"'; ?>>
          <?= $contents ?>
			  </div>

        <div id="footer">
          &copy; PHPures3 2023
        </div>
      </div>
		</div>
	</div>
</body>

</html>
