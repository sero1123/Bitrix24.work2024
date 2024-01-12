<?

require_once 'filter_result.php';
#зададим заголовок
$APPLICATION->SetTitle('Список задач');

$rows = [];

$filterFilds = [
    ['id' => 'ID', 'name' => 'ID', 'type' => 'number', 'default' => true],
    ['id' => 'TITLE', 'name' => 'TITLE', 'type' => 'string', 'default' => true],
    ['id' => 'RESPONSIBLE_NAME', 'name' => 'RESPONSIBLE_NAME', 'type' => 'string', 'default' => true],
    ['id' => 'GROUP_NAME', 'name' => 'GROUP_NAME', 'type' => 'string', 'default' => true],
    ['id' => 'CREATED_BY_NAME', 'name' => 'CREATED_BY_NAME', 'type' => 'string', 'default' => true],
    ['id' => 'TIME_SPENT', 'name' => 'TIME_SPENT', 'type' => 'string', 'default' => true],
    ['id' => 'TIME_ESTIMATE', 'name' => 'TIME_ESTIMATE', 'type' => 'string', 'default' => true],
    ['id' => 'DEADLINE', 'name' => 'DEADLINE', 'type' => 'date', 'default' => true],


];

$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
    'FILTER_ID' => 'tasks_list',
    'GRID_ID' => 'tasks_list',
    'FILTER' => $filterFilds,
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true,
]);


$filterOption = new Bitrix\Main\UI\Filter\Options('tasks_list');
$filterData = $filterOption->getFilter($filterFilds);

$logicFilter = Bitrix\Main\UI\Filter\Type::getLogicFilter($filterData, $filterFilds);

if ($logicFilter)
{
    $filterResult = new FilterResult($logicFilter, $arResult);
    $arResult = $filterResult->getFilteredResult();
    
}
    


foreach($arResult['TASKS'] as $task){
    if(!$task) continue;
    
    $rows[]['data'] = [
        'ID' => (int) $task['ID'],
        'TITLE' => (string) "<a href={$task['URL_TASK']}>" . $task['TITLE'] . "</a>",
        'RESPONSIBLE_NAME' => (string) "<a href='/company/personal/user/{$task['RESPONSIBLE_ID']}/'>" . $task['RESPONSIBLE_NAME'] . "</a>",
        'GROUP_NAME' => (string) "<a href={$task['URL_GROUP']}>" . $task['GROUP_NAME'] . "</a>",
        'CREATED_BY_NAME' => (string) "<a href='/company/personal/user/{$task['CREATED_BY']}/'>" . $task['CREATED_BY_NAME'] . "</a>",
        'TIME_SPENT' => (string)$task['TIME_SPENT'],
        'TIME_ESTIMATE' => (string)gmdate('H:i:s',$task['TIME_ESTIMATE']),
        'DEADLINE' => (string)$task['DEADLINE'],
    ];
}

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [ 
    'GRID_ID' => 'tasks_list', 
    'COLUMNS' => 
    [
        ['id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true],
        ['id' => 'TITLE', 'name' => 'Название задачи', 'sort' => 'TITLE', 'default' => true],
        ['id' => 'RESPONSIBLE_NAME', 'name' => 'Отвественный', 'sort' => 'RESPONSIBLE_NAME', 'default' => true],
        ['id' => 'GROUP_NAME', 'name' => 'Группа', 'sort' => 'GROUP_NAME', 'default' => true],
        ['id' => 'CREATED_BY_NAME', 'name' => 'Постановщик', 'sort' => 'CREATED_BY_NAME', 'default' => true],
        ['id' => 'TIME_SPENT', 'name' => 'Время потрачено', 'sort' => 'TIME_SPENT', 'default' => true],
        ['id' => 'TIME_ESTIMATE', 'name' => 'Время выделено', 'sort' => 'TIME_ESTIMATE', 'default' => true],
        ['id' => 'DEADLINE', 'name' => 'Крайний срок', 'sort' => 'DEADLINE', 'default' => true],
    ],
    'ROWS' => $rows,
    'SHOW_ROW_CHECKBOXES' => true, 
    'NAV_OBJECT' => $nav, 
    'AJAX_MODE' => 'Y', 
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''), 
    'PAGE_SIZES' => [ 
        ['NAME' => "5", 'VALUE' => '5'], 
        ['NAME' => '10', 'VALUE' => '10'], 
        ['NAME' => '20', 'VALUE' => '20'], 
        ['NAME' => '50', 'VALUE' => '50'], 
        ['NAME' => '100', 'VALUE' => '100'] 
    ], 
    'AJAX_OPTION_JUMP'          => 'Y', 
    'SHOW_CHECK_ALL_CHECKBOXES' => true, 
    'SHOW_ROW_ACTIONS_MENU'     => true, 
    'SHOW_GRID_SETTINGS_MENU'   => true, 
    'SHOW_NAVIGATION_PANEL'     => true, 
    'SHOW_PAGINATION'           => true, 
    'SHOW_SELECTED_COUNTER'     => true, 
    'SHOW_TOTAL_COUNTER'        => true, 
    'SHOW_PAGESIZE'             => true, 
    'ALLOW_COLUMNS_SORT'        => true, 
    'ALLOW_COLUMNS_RESIZE'      => true, 
    'ALLOW_HORIZONTAL_SCROLL'   => true, 
    'ALLOW_SORT'                => true, 
    'ALLOW_PIN_HEADER'          => true, 
    'AJAX_OPTION_HISTORY'       => 'N' ,
]);



?>
