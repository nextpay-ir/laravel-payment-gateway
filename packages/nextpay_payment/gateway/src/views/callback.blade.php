<!DOCTYPE html>
<html>
    <head>
        <title>نتیجه پرداخت با نکست پی</title>

        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

        <style>
        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            display: table;
            font-weight: bold;
            font-family: 'Lato';
            direction: rtl;
        }

        .container {
            text-align: center;
            display: table-cell;
            vertical-align: middle;
        }

        .content {
            text-align: center;
            display: inline-block;
        }

        .title {
            font-size: 20px;
        }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title"><span>شماره تراکنش : </span>{{ $trans_id }}</div>
                <div class="title"><span>شماره سفارش : </span>{{ $order_id }}</div>
                <div class="title"><span>کد وضعیت : </span>{{ $status }}</div>
            </div>
        </div>
    </body>
</html>