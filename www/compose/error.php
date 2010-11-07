<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'..');
    require_once 'config.php';
    require_once 'lib.php';
    
    $headers = apache_request_headers();

    $paper = null;
    $format = null;
    
    if(is_array($headers))
    {
        $paper = isset($headers['X-Print-Paper']) ? $headers['X-Print-Paper'] : null;
        $format = isset($headers['X-Print-Format']) ? $headers['X-Print-Format'] : null;
    }
    
    if(is_null($paper) || is_null($format))
    {
        header('HTTP/1.1 400');
        die("Missing required X-Print-Paper and X-Print-Format headers.\n");
    }
    
    $ctx = default_context();
    
    $recipient_id = $_GET['id'];
    
    $recipient = get_recipient($ctx, $recipient_id);
    $map = get_map($ctx, $recipient['map_id']);
    
    $message = sprintf('Offline error for recipient %s, map %s, %s/%s: %s',
                       $recipient['id'],
                       $map['id'],
                       $paper, $format,
                       trim(file_get_contents('php://input')));
    
    error_log($message);
    
    $ctx->close();

    header('HTTP/1.1 500');
    echo "{$message}\n";

?>
