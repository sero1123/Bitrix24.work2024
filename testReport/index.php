<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');?>
<?
$APPLICATION->IncludeComponent(
    'vedita:time_report',
    '.default',
    [],
);

?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');?>
