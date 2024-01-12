<?
use Bitrix\Main\Mail\Event;
use Bitrix\Tasks;
use Bitrix\Tasks\Item\Task;

#добавить событие добавления бюджета в задачу
//AddEventHandler('tasks', 'OnBeforeTaskAdd', 'addBudget');

function addBudget(array $arFields) : void
{
    $budget = $arFields['UF_TASKS_F_1'];
    $parentId = $arFields['PARENT_ID'];
    $parentBudget = false;

    while ($parentId && !$parentBudget)
    {
        $task = new Task($parentId);
        $arFieldsTaskParent = $task->getData();
        $parentBudget = $arFieldsTaskParent['UF_TASKS_F_1'];
        $parentId = $arFieldsTaskParent['PARENT_ID'];
    }
    if (!($budget || $parentBudget)) 
    {
        #выкинуть оошибку что нет бюджета
        throw new Tasks\Exception('Нет бюджета');

    }
};