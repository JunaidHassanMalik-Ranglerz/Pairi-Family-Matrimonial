<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions</title>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: "Heebo", sans-serif;
        }

        body {
            margin: 0;
            font-size: 16px;
        }

        .banner {
            background: linear-gradient(115deg, rgba(102, 125, 255, 1) 0%, rgba(122, 140, 255, 1) 89%);

            padding: 60px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: black;
            margin-bottom: 20px;
            animation: slideFromTop 1s ease-out; /* Adjust the duration and easing as needed */
        }

        .banner h1 {
            font-size: 80px;
            color: white;
            text-shadow: 0 2px 6px rgba(0,0,0,.459);
        }

        .container {
            padding: 0 20px;
            animation: slideFrombottom 1s ease-out;
        }

        @keyframes slideFromTop {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideFrombottom {
            from {
                transform: translateY(100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 991.5px) and (min-width: 400px) {
            .banner h1 {
                font-size: 40px;
            }
            .banner {
                padding: 32px 0;
            }
            .container {
                padding: 0 15px;
            }
            body {
                font-size: 14px;
            }
        }

        @media (max-width: 399px) {
            .banner h1 {
                font-size: 30px;
            }
            .banner {
                padding: 30px 0;
            }
            .container {
                padding: 0 7px;
            }
            body {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <div class="banner">
        <h1>Terms & Conditions</h1>
    </div>

    <div class="container">
        {!! $data->description !!}
    </div>

</body>
</html>
