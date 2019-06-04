<?php

function getSinceId($file, $format = null)
{
    $since_id = @file_get_contents(__DIR__ . '/../tmp/' . $file);
    if (!$since_id) {
        switch ($format) {
            case 'DateTime':
                $since_id = '1970-01-03 00:00:00';
                break;
            default:
                $since_id = 0;
        }
    }
    return $since_id;
}

function setSinceId($file, $max_id = null)
{
    file_put_contents(__DIR__ . '/../tmp/' . $file, $max_id);
}
