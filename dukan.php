<?php
/**
 * The Dukan section has been removed from ARCHIVE.
 * Keep this legacy URL working by returning visitors to the homepage.
 */
header('Location: index.php', true, 301);
exit;
