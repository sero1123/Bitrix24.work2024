<?
      CJSCore::Init(array("jquery"));
 ?>
<table>
    <tr>
        <th>ФИО сотрудника</th>
        <th>Отдел</th>
        <th>Количество часов, затраченных на задачи конкретного проекта, за указанный диапазон</th>
        <th>Количество часов, затраченных на все задачи, за указанный диапазон</th>
        <th>Количество дней, которые сотрудник был в отпуске, за указанный диапазон</th>
    </tr>
    <? foreach ($arResult as $item): ?>
        <tr>
            <td><?= $item['NAME'] ?></td>
            <td><?= $item['DEPARTMENT'] ?></td>
            <td>
                <ul>
                    <? foreach($item['PROJECT_TIME_H'] as $key => $value){?>
                        <li><?= $key . ' - ' . $value ?></li>
                    <?}?>
                </ul>
            </td>
            <td><?= $item['ALL_TIME_FOR_TASKS_H'] ?></td>
            <td><?= $item['ABSENCE'] ?></td>
        </tr>
    <? endforeach; ?>

    <button id='excel'>Получить excel отчет</button>
    <a href=""></a>
</table>    

<script>
$(document).ready(function(){
    $('#excel').click(function(){
        console.log('click')
        BX.ajax.runComponentAction('vedita:time_report', 'excelExport', {
            mode: 'class',
            data: {
                'post': {'DATA': <?=json_encode($arResult)?>},
            },
        }).then(function(response){
            console.log(response.data['response'])
            window.location.href = response.data['response']
        })
    })
})

</script>