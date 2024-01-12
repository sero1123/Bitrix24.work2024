<?
function logWrite($data)
{
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/log.txt', var_export($data, true));
}