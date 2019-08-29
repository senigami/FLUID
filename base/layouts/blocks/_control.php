<?php
$pathParts = explode('/', $fluid->site->url );
$block     = $pathParts[0] == 'block' ? $pathParts[1]:'text';
$fluid->loadPathFile("/layouts/blocks/$block.php");
