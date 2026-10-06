<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&amp;family=Nunito:wght@600;700;800&amp;family=Pacifico&amp;display=swap" rel="stylesheet">
<style>
    *{
        font-family: "Heebo", sans-serif;
    }

    body {
        margin: 0;
        font-size:16px;
    }

    .banner {
        background: linear-gradient(115deg, rgba(102, 125, 255, 1) 0%, rgba(122, 140, 255, 1) 89%);

        padding:60px 0;
        display: flex;
        justify-content:center;
        align-items:center;
        color:black;
        margin-bottom:20px;
    }

    .banner h1 {
        font-size:80px;
        color:white;
        text-shadow: 0 2px 6px rgba(0,0,0,.459);

    }
    .container {
        padding:0 20px;
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
.banner {
    animation: slideFromTop 1s ease-out; / Adjust the duration and easing as needed /
}
.container {
    animation: slideFrombottom 1s ease-out
}
    @media (max-width:991.5px ) and (min-width:400px) {
        .banner h1{
            font-size:40px;
        }.banner{
        padding:32px 0;
       }
       .container{
        padding:0 15px;
    }
    body {
        margin: 0;
        font-size:14px;
    }
    }
    @media(max-width:399px ){
        .banner h1{
            font-size:30px;
        }.banner{
        padding:30px 0;
       }
       .container{
        padding:0 7px;
    }
        body {
        margin: 0;
        font-size:14px;
    }
}

</style>
<div class="banner">
    <h1>Privacy Policy</h1>
</div>
<div class="container">
    {!! $data->description !!}
</div>

