<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CModule::IncludeModule("intranet");
CModule::IncludeModule("tasks");
CModule::IncludeModule("socialnetwork");
use Bitrix\Tasks\Dispatcher\PublicAction\Task\DayPlan\Timer;
use Bitrix\Tasks\Internals\Task\TimerTable;
use Bitrix\Tasks\TaskTable;
// use Bitrix\Main\TaskTable;


class TasksList extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        if(!$arParams['USER_ID'])
        {
            global $USER;
            $arParams['USER_ID'] = $USER->getID();
        }
        return $arParams;
    }
    public function executeComponent(): void
    {
        $this->getGroups();
        $this->getUsers();
        $this->getSubordinate();
        $this->getTasks();
        $this->getTimers();
        $this->updateTasksResult();

        $this->includeComponentTemplate();
    }

    private function getGroups(): void
    {
        $groups = CSocNetGroup::GetList();
        while ($group = $groups->GetNext())
        {
            $this->arResult['GROUPS'][] = $group;
        }
    }

    private function getUsers(): void
    {
        $users = CUser::GetList();
        while ($user = $users->GetNext())
        {
            $this->arResult['USERS'][] = $user;
        }
    }

    private Function getSubordinate(): void
    {
        $arUsers = CIntranetUtils::getSubordinateEmployees($this->arParams['USER_ID']);
        while ($user = $arUsers->GetNext())
        {
            $this->arResult['SUBORDINATE'][] = $user['ID'];
        }
    }

    private function getTasks(): void
    {
        foreach($this->arResult['SUBORDINATE'] as $subordinate)
        {
            $tasks = TaskTable::getList
            ([
                'select' => ['*'],
                'order' => ['ID' => 'ASC'],
                'filter' => ['=STATUS' => '3', '=RESPONSIBLE_ID' => $subordinate]
            ])->Fetch();
            $this->arResult['TASKS'][] = $tasks;
        }
    }

    private function updateTasksResult(): void
    {
        foreach($this->arResult['TASKS'] as &$task)
        {
            if (!$task) continue;
            
            if($task['GROUP_ID'])
            {
                foreach($this->arResult['GROUPS'] as $group)
                {
                    if($group['ID'] == $task['GROUP_ID'])
                    {
                        $task['GROUP_NAME']= $group['NAME'];
                    }
                    break;
                }
            }else $task['GROUP_NAME'] = 'без группы';

            if($task['CREATED_BY'])
            {
                foreach($this->arResult['USERS'] as $user)
                {
                    if($user['ID'] == $task['CREATED_BY'])
                        $task['CREATED_BY_NAME'] = $user['NAME'] . ' ' . $user['LAST_NAME'];
                    break;
                }
            }
            if($task['RESPONSIBLE_ID'])
            {
                foreach($this->arResult['USERS'] as $user)
                {
                    if($user['ID'] == $task['RESPONSIBLE_ID'])
                        $task['RESPONSIBLE_NAME'] = $user['NAME'] . ' ' . $user['LAST_NAME'];
                    break;
                }
            }
            foreach($this->arResult['TIMERS'] as $timer)
            {
                if($timer['TASK_ID'] != $task['ID'])
                    continue;
                    
                $timeSpent = time() - $timer['TIMER_STARTED_AT'];
                $timeSpentHour = (int) ($timeSpent/3600);
                $timeSpentMin = (int) ($timeSpent%3600/60);
                $timeSpentSec = $timeSpent%60;                    
                $task['TIME_SPENT'] = $timeSpentHour . 'ч ' . $timeSpentMin . 'м ' . $timeSpentSec . 'с';
                
            }
            $this->getUrl($task);
        }
    }

    private function getTimers(): void
    {
        $timers = TimerTable::getList([
            'select' => ['*'],
        ]);

        while($timer = $timers->fetch())
        {
            $this->arResult['TIMERS'][] = $timer;
        }
    }

    private function getUrl(array &$task) : void
    {
        $task['URL_TASK'] = '/company/personal/user/' . $task['RESPONSIBLE_ID'] . '/tasks/task/view/' . $task['ID'] . '/';
        $task['URL_GROUP'] = '/workgroups/group/' . $task['GROUP_ID'] . '/';
    }
}