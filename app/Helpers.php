<?php

/**
 * 缩进数据，以json在HTML上展示
 */
function indentToJson($data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT);
    $search = ["\n", " "];
    $replace = ['<br>', '&nbsp;'];
    return str_replace($search, $replace, $json);
}