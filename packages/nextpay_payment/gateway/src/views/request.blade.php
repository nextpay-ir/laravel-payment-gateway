<!DOCTYPE html>
<html>
    <head>
        <title>انتقال به درگاه پرداخت نکست  پی</title>

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
            font-weight: 100;
            font-family: 'Lato';
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
            font-size: 96px;
        }
        </style>
    </head>
    <body>
        <script>
            var form = document.createElement("form");
            form.setAttribute("method", "GET");
            form.setAttribute("action", "{{$request}}/{{$trans_id}}");
            form.setAttribute("target", "_self");
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        </script>
        <div class="container">
            <div class="content">
                <div class="title">درحال انتقال به نکست پی</div>
            </div>
        </div>
    </body>
</html>