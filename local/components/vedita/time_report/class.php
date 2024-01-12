<?
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Tasks\TaskTable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class TimeReport extends CBitrixComponent implements Controllerable
{
    private $arTmp;
    private $dateStart;
    private $dateEnd;
    private $firstNumber = 2;
    public function onPrepareComponentParams($arParams)
    {
        $this->arTmp = [];
        CModule::IncludeModule("tasks");
        CModule::IncludeModule("socialnetwork");
        CModule::IncludeModule("intranet");
        CModule::IncludeModule("timeman");   

        $this->dateEnd = date('Y-m-d');
        $this->dateStart = date('Y-m-d', 1);
        if ($arParams['DATE_START'])
        {
            $this->dateStart = $arParams['DATE_START'];
        }
        if ($arParams['DATE_END'])
        {
            $this->dateEnd = $arParams['DATE_END'];
        }
    }

    public function configureActions()
    {
        return [
            'sendMessage' => [
                'prefilters' => [],
            ],
        ];
    }
    public function executeComponent()
    {
        $this->getData();
        $this->dataProcessing();
        // $this->testPrint();
        $this->includeComponentTemplate();

    }

    public function excelExportAction(array $post)
    {

        $arSheet = $this->initSpred();
        $this->excelWrite($arSheet['worksheet'], $post['DATA']);
        $result =  $this->respronseResult($arSheet['spreadSheet']);
        return ['response' => $result];
    }


    private function testPrint()
    {
        echo '<pre>';
        print_r($this->arTmp);
        print_r($this->arResult);
        echo '</pre>';
    }

    private function getData(): void
    {
        $this->getGroup();
        $this->getTimeReport();
        $this->getTasksReport();
        $this->getUsers();
        $this->getTasks();
        $this->getDepartament();
        $this->getAbsence();
    }

    private function dataProcessing(): void
    {
        $this->processingUsers();
        $this->processingDepartament();
        $this->processingTimeReportProjects();
        $this->processingTimeReportTasks();
        $this->processingAbsence();
        $this->processingTime();

    }

    private function initSpred(): array
    {
        $spreadSheet = new Spreadsheet();
        $sheet = $spreadSheet->getActiveSheet();
        return ['spreadSheet' => $spreadSheet, 'worksheet' => $sheet];
    }

    private function excelWrite(Worksheet &$sheet, array $post): void
    {
        $this->writeTitleExcel($sheet);
        $this->writeDataExcel($sheet, $post);
    }

    private function respronseResult(Spreadsheet $spreadSheet)
    {
        global $USER;
        $id = $USER->GetID();
        $writer = new Xlsx($spreadSheet);
        $filename = "hello_worldasda_$id.xlsx";
        $writer->save($filename);
        return ("/bitrix/services/main/$filename");
    }

    private function writeTitleExcel(Worksheet &$sheet): void
    {
        $sheet->setCellValue('A1', 'ФИО');
        $sheet->setCellValue('B1', 'ОТДЕЛ');
        $sheet->setCellValue('C1', 'ПРОЕКТЫ');
        $sheet->setCellValue('D1', 'ВРЕМЯ НА ПРОЕКТЫ');
        $sheet->setCellValue('E1', 'ЗАТРАЧЕННОЕ ВРЕМЯ НА ПРОЕКТЫ (%)');
        $sheet->setCellValue('F1', 'ВРЕМЯ НА ВСЕ ЗАДАНИЯ');
        $sheet->setCellValue('G1', 'ОТПУСК (ДНИ)');
    }

    private function writeDataExcel(Worksheet &$sheet, array $post): void
    {
        $i1 = $this->firstNumber;
        $i2 = $this->firstNumber;
        foreach ($post as $user)
        {
            $sheet->setCellValue('A'.$i1, $user['NAME']);
            $sheet->setCellValue('B'.$i1, $user['DEPARTMENT']);
            foreach ($user['PROJECT_TIME'] as $key => $value)
            {
                $sheet->setCellValue('C'.$i2, $key);

                $sheet->setCellValue('D'.$i2, "=$value/86400");
                $sheet->getStyle('D'.$i2)->getNumberFormat()->setFormatCode('[H]:mm:ss');

                $sheet->setCellValue('E'.$i2, "=D$i2/F$i2*100");

                $i2++;
            }

            $time = $user['ALL_TIME_FOR_TASKS'];
            $sheet->setCellValue('F'.$i1, "=$time/86400");
            $sheet->getStyle('F'.$i1)->getNumberFormat()->setFormatCode('[H]:mm:ss');

            $sheet->setCellValue('G'.$i1, $user['ABSENCE']);
            $i2++;$i1=$i2;
        }
    }
    
    private function getGroup(): void
    {
        $groups = CSocNetGroup::GetList();
        while ($group = $groups->GetNext())
        {
            $this->arTmp['GROUPS'][] = $group;
        }
    }

    private function getTimeReport(): void
    {
    $reports =  CTimeManEntry::GetList();
        while ($report = $reports->GetNext())
        {
            $dateStart = new DateTime($report['DATE_START']);
            $dateEnd = new DateTime($report['DATE_FINISH']);
            $selfDateStart = new DateTime($this->dateStart);
            $selfDateEnd = new DateTime($this->dateEnd);
            if ($dateStart >= $selfDateStart && $dateEnd <= $selfDateEnd)
                $this->arTmp['TIME_REPORT'][] = $report;
        }
    }

    private function getTasksReport(): void
    {
        $reports =  CTimeManReportDaily::GetList();
        $i = 0;
        while ($report = $reports->GetNext())
        {
            $dateReport = new DateTime($report['REPORT_DATE']);
            $selfDateStart = new DateTime($this->dateStart);
            $selfDateEnd = new DateTime($this->dateEnd);

            if ( $dateReport >= $selfDateStart && $dateReport <= $selfDateEnd)
            {
                $this->arTmp['TASKS_REPORT'][$i] = $report;
                $this->arTmp['TASKS_REPORT'][$i]['TASKS'] = unserialize(htmlspecialchars_decode($report['TASKS']));
                $i++;
            }
        }
    }

    private function getUsers(): void
    {
        $users = CUser::GetList();
        
        while ($user = $users->GetNext())
        {
            $this->arTmp['USERS'][] = $user;
        }
    }

    private function getDepartament(): void
    {
        $departaments = CIBlockSection::GetList(
            [],
            ['IBLOCK_CODE' => 'departments'],
        );
        while ($departament = $departaments->GetNext())
        {
            $this->arTmp['DEPARTAMENTS'][] = $departament;
        }
    }

    private function getTasks(): void
    {
        $tasks = TaskTable::GetList();
        while ($task = $tasks->fetch())
        {
            $this->arTmp['TASKS'][] = $task;
        }
    }

    private function getAbsence(): void
    {
        $absences = CIBlockElement::GetList(
            [],
            ['IBLOCK_CODE' => 'absence'],
        );
        while ($absence = $absences->GetNext())
        {
            $this->arTmp['ABSENCE'][] = $absence;
        }
    }

    private function processingUsers(): void
    {
        foreach ($this->arTmp['USERS'] as $key => $user)
        {
            $this->arResult[$user['ID']]['NAME'] = $user['NAME'] . ' ' . $user['LAST_NAME'];
            $this->arResult[$user['ID']]['DEPARTMENT'] = $user['UF_DEPARTMENT'][0];
            $this->arResult[$user['ID']]['LOGIN'] = $user['LOGIN'];
        }
    }

    private function processingDepartament(): void
    {
        foreach ($this->arResult as $userId => &$userData)
        {
            foreach ($this->arTmp['DEPARTAMENTS'] as $departament)
            {
                if ($departament['ID'] == $userData['DEPARTMENT'])
                {
                    $userData['DEPARTMENT'] = $departament['NAME'];
                }
            }
        }
    }

    private function processingTimeReportProjects(): void
    {
        $this->taskBasedTimingForThePeriod();
        $this->taskGroupingByProject();
        $this->calculationProjectTime();
    }

    private function processingTimeReportTasks(): void
    {
        foreach ($this->arResult as $userId => $userData)
        {
            $this->arResult[$userId]['ALL_TIME_FOR_TASKS'] = $this->calculationTaskTimeForUser($userId);
        }
    }

    private function processingAbsence(): void
    {
        foreach ($this->arResult as $userId => $userData)
        {
            $this->arResult[$userId]['ABSENCE'] = $this->calculationAbsenceForUser($userData['LOGIN']);
        }
    }    

    private function taskGroupingByProject(): void
    {
        foreach ($this->arTmp['TASKS'] as $task)
        {
            $this->arTmp['TASKS_PROJECT_ID'][$task['GROUP_ID']][] = $task;
        }
        foreach ($this->arTmp['TASKS_PROJECT_ID'] as $id => $tasks)
        {
            foreach ($this->arTmp['GROUPS'] as $group)
            {
                if ($group['ID'] == $id)
                {
                    $this->arTmp['TASKS_PROJECT_NAME'][$group['NAME']] = $tasks;
                }
            }
        }
    }

    private function taskBasedTimingForThePeriod(): void
    {
        foreach ($this->arTmp['TASKS'] as &$task)
        {
            foreach ($this->arTmp['TASKS_REPORT'] as $report)
            {
                foreach ($report['TASKS'] as $taskReport)
                {
                    if ($taskReport['ID'] == $task['ID'])
                    {
                        $task['USER_TIME'][$report['USER_ID']][] = $taskReport['TIME'];
                    }
                }
            }
        }
    }

    private function calculationProjectTime(): void
    {
        foreach ($this->arResult as $userId => $userData)
        {
            $this->arResult[$userId]['PROJECT_TIME'] = $this->calculationProjectTimeForUser($userId);
        }
    }

    private function calculationProjectTimeForUser(int $userId): array
    {
        $result = [];
        foreach ($this->arTmp['TASKS_PROJECT_NAME'] as $projectName => $task)
        {
            foreach ($task[0]['USER_TIME'][$userId] as $time)
            {
                $result[$projectName] += $time;
            }
        }
        return $result;
    }

    private function calculationTaskTimeForUser(int $userId): int
    {
        $result = 0;
        foreach($this->arTmp['TASKS_REPORT'] as $report)
        {
            if($report['USER_ID'] == $userId)
            {
                foreach ($report['TASKS'] as $task)
                {
                    $result += $task['TIME'];
                }
            }
        }
        return $result;
    }

    private function calculationAbsenceForUser(string $userLogin): int
    {
        $result = 0;
        foreach($this->arTmp['ABSENCE'] as $absence)
        {
            $a = str_replace(' ', '',str_replace(')', '',str_replace('(', '',$absence['USER_NAME'])));
            if ($a == $userLogin)
            {
                $result += $this->calculationAbsenceDay($absence);
            }
        }
        return $result;
    }

    private function calculationAbsenceDay(array $absence): int
    {
        $result = 0;
        $dateStart = new DateTime($absence['ACTIVE_FROM']);
        $dateEnd = new DateTime($absence['ACTIVE_TO']);
        $selfDateStart = new DateTime($this->dateStart);
        $selfDateEnd = new DateTime($this->dateEnd);
        $a = 0;
        if ($dateEnd < $selfDateStart || $dateStart > $selfDateEnd)
        {
            return 0;
        }
        
        if ($dateStart < $selfDateStart)
        {
            $dateStart = $selfDateStart;
        }
        if ($dateEnd > $selfDateEnd)
        {
            $dateEnd = $selfDateEnd;
        }
        $result = ($dateEnd->getTimestamp() - $dateStart->getTimestamp())/(60*60*24)+1;
        return $result;
    }

    private function processingTime(): void
    {
        foreach ($this->arResult as $userId => $userData)
        {
            $timeTasks = $this->arResult[$userId]['ALL_TIME_FOR_TASKS'];
            $this->arResult[$userId]['ALL_TIME_FOR_TASKS_H'] = floor($timeTasks/3600)."h:".floor(($timeTasks%3600)/60)."m:".($timeTasks%60) ."s";
            foreach ($userData['PROJECT_TIME'] as $projectName => $time)
            {
                $this->arResult[$userId]['PROJECT_TIME_H'][$projectName] = floor($time/3600)."h:".floor(($time%3600)/60)."m:".($time%60) ."s";
            }
        }
    }
}