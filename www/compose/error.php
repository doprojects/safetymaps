<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'../../lib');

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
    
    mysql_query('BEGIN', $ctx->db);
    
    $errored = error_recipient($ctx, $recipient_id, $paper, $format);
    
    if($errored === false) {
        header('HTTP/1.1 500');
        mysql_query('ROLLBACK', $ctx->db);
    
    } elseif(is_null($errored)) {
        header('HTTP/1.1 200');
        mysql_query('COMMIT', $ctx->db);
    
    } else {
        header('HTTP/1.1 201');
        mysql_query('COMMIT', $ctx->db);
    }

    $ctx->close();

    echo "{$message}\n";
    
?>
