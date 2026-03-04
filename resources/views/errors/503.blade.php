<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance - BigFun</title>
    <style>
        body {
            background-color: #FDF2F4;
            font-family: sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            color: #2D3748;
        }

        h1 {
            font-size: 3rem;
            color: #9E6B73;
            margin-bottom: 10px;
        }

        p {
            font-size: 1.2rem;
            color: #718096;
            max-width: 500px;
            line-height: 1.5;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #9E6B73;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-top: 30px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <h1>We'll be right back!</h1>
    <p>BigFun is currently undergoing scheduled maintenance to improve our systems. Please check back in a few minutes.</p>
    <div class="loader"></div>
</body>

</html>