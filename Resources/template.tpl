<?php

if (!extension_loaded('opcache')) {
    $success = false;
    $message = 'Opcode cache extension not loaded';
} if (opcache_reset()) {
    $success = true;
    $message = 'Opcode cache clear: success';
} else {
    $success = false;
    $message = 'Opcode cache clear: failure';
}

die(json_encode(array('success' => $success, 'message' => $message)));
