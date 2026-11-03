<?php
function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function personUrl($action = 'index', $id = null) { return 'index.php?' . http_build_query(['page' => 'person', 'action' => $action, 'id' => $id]); }
function textLength($value) { return preg_match_all('/./us', $value, $unused); }
function donationUrl($action = 'index', $id = null) { return 'index.php?' . http_build_query(['page' => 'donation', 'action' => $action, 'id' => $id]); }
function partyUrl($action = 'index', $id = null) { return 'index.php?' . http_build_query(['page' => 'party', 'action' => $action, 'id' => $id]); }
