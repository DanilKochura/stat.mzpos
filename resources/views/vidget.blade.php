

<!DOCTYPE html>
<html lang="ru">
<head>
    <title>amoCRM Test Dashboard Widget</title>


    <style>
        a {
            width: 100%;
            background: #2E3640;
            height: 100%;
            text-align: center;
            text-decoration: none;
            color: white;
        }
        body
        {
            width: 100%;
            height: 100vh;
            display: flex;
            background: #2E3640;
            flex-direction: row;
            flex-wrap: nowrap;
            align-content: center;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
    </style>
</head>

<body class="">
<div>
    <a href="https://stat.mzpo-s.ru/managers/{{\Carbon\Carbon::yesterday()->startOfMonth()->format('d-m-Y')}}|{{Carbon\Carbon::yesterday()->format('d-m-Y')}}?token={{time()}}" target="_blank">Открыть конверсию</a>
</div>
</body>
</html>














