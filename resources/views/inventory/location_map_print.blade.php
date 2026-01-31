<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Map (Denah)</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .container {
            width: 95%;
            height: 95%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .title {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #333;
            background: rgba(255, 255, 255, 0.8);
            padding: 5px 10px;
            border-radius: 4px;
        }

        img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .title {
                display: none;
            }

            /* Optional: Hide title on print if it covers content */
        }
    </style>
</head>

<body>
    <div class="title">Warehouse Map - {{ date('Y-m-d') }}</div>

    <div class="container">
        <img src="{{ asset('assets/denah_warehouse.jpeg') }}" alt="Denah Warehouse">
    </div>

    <script>
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 500); // Slight delay to ensure image loads
        };
    </script>
</body>

</html>