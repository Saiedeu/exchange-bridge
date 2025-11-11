<?php 

/**
 * ExchangeBridge - 304: Not Modified Page
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

// 304 Not Modified should not display any content according to HTTP specification
// The client should use its cached version

// Set 304 header and exit immediately
header("HTTP/1.0 304 Not Modified");

// Remove any content headers
header_remove('Content-Type');
header_remove('Content-Length');

// Exit without any body content
exit;
?>