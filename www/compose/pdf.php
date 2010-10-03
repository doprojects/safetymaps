<?php

    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.'..');
    require_once 'config.php';
    require_once 'lib.php';
    
    function save_pdf(&$ctx, $src_filename, $recipient_id)
    {
        $recipient = get_recipient($ctx, $recipient_id);
        $map = get_map($ctx, $recipient['map_id'], false);
        
        $map_dirname = dirname(__FILE__)."/../files/{$map['id']}";
        @mkdir($map_dirname);
        @chmod($map_dirname, 0775);
        
        $pdf_dirname = realpath("{$map_dirname}/{$recipient['id']}");
        @mkdir($pdf_dirname);
        @chmod($pdf_dirname, 0775);
        
        $pdf_filename = "{$pdf_dirname}/{$map['properties']['paper']}-{$map['properties']['format']}.pdf";
        $pdf_content = file_get_contents($src_filename);
    
        $fp = fopen($pdf_filename, 'w');
        fwrite($fp, $pdf_content);
        fclose($fp);
        chmod($pdf_filename, 0664);
    }

    $db = mysql_connect(MYSQL_HOSTNAME, MYSQL_USERNAME, MYSQL_PASSWORD);
    mysql_select_db(MYSQL_DATABASE, $db);
    $ctx = new Context($db);
    
    $recipient_id = $_GET['id'];
    
    save_pdf($ctx, 'php://input', $recipient_id);
    
    mysql_query('BEGIN', $ctx->db);
    
    $finished = finish_recipient($ctx, $recipient_id);
    
    if($finished) {
        header('HTTP/1.1 200');
        mysql_query('COMMIT', $ctx->db);
    
    } else {
        header('HTTP/1.1 400');
        mysql_query('ROLLBACK', $ctx->db);
    }

    $ctx->close();

    echo strlen(file_get_contents('php://input'));

?>
