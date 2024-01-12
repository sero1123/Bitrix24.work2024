<?
# пример =ID: 13
class FilterResult
{
    private $logicFilter;
    private $arResult;
    public function __construct(array $logicFilter, array $arResult)
    {
        $this->logicFilter = $logicFilter;
        $this->arResult = $arResult;
        
    }
    /**
     * Получает отфильтрованный результат.
     *
     * @return array Отфильтрованный результат.
     */
    public function getFilteredResult() : array
    {
        $arrLogicFilter = $this->parseLogicFilter();
        foreach($arrLogicFilter as $field => $compare)
        {
            $res = $this->filterOneField($field, $compare);
            $this->arResult['TASKS'] = $res;
        }
        return $this->arResult;
    }
    /**
     * Парсирует массив логического фильтра и возвращает результат в виде ассоциативного массива.
     *
     * @return array Разобранный массив логического фильтра.
     */
    private function parseLogicFilter() : array
    {
        $arrLogicFilter = [];
        #приходит массив в формате 1 => '<=NAME' сделать массив ['<=' => 'NAME'] обрезать за счет регулярного выражения
        foreach($this->logicFilter as $logicFunction=>$value){

            $pattern = "/([<>=]+)(\w+)/";
            preg_match($pattern, $logicFunction, $matches);
            $result = array_slice($matches, 1);    

            if ($result)
                $arrLogicFilter[$result[1]] = ['logicOperator' => $result[0],
                                                'value' => $value];     
            else
                $arrLogicFilter[$logicFunction] = ['logicOperator' =>'',
                                                    'value' => $value];
        }
        return $arrLogicFilter;
    }
    /**
     * Фильтрует массив на основе указанного поля и значений для сравнения.
     *
     * @param string $field Поле для фильтрации.
     * @param array $compare Массив, содержащий логический оператор сравнения и значение.
     * @return array Отфильтрованный массив.
     */
    private function filterOneField(string $field, array $compare) : array
    {
        $result = [];
        foreach ($this->arResult['TASKS'] as $task)
        {        
            switch($compare['logicOperator'])
            {
                case '<=':
                    if($task[$field] <= $compare['value'])
                        $result[] = $task;
                    break;
                case '>=':
                    if($task[$field] >= $compare['value'])
                        $result[] = $task;
                    break;
                case '>':
                    if($task[$field] > $compare['value'])
                        $result[] = $task;
                    break;
                case '<':
                    if($task[$field] < $compare['value'])
                        $result[] = $task;
                    break;
                case '=':
                    if($task[$field] == $compare['value'])
                        $result[] = $task;
                    break;
                case '!=':
                    if($task[$field] != $compare['value'])
                        $result[] = $task;
                    break;
                
                case '':
                    if(str_contains($task[$field], $compare['value']))
                        $result[] = $task;
                    break;
            }
        }
        return $result;
    }
}