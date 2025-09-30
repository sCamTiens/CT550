<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Website siêu thị mini">
    <link href="/images/minigo.png" rel="shortcut icon" type="image/vnd.microsoft.icon" />
    <?php
    $title = "MiniGo";
    $name = "MiniGo";
    ?>
    <title><?php echo $title ?></title>
    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- animate -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <!-- icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />

    <!-- font chữ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Potta+One&display=swap" rel="stylesheet">

    <link href="/css/style.css" rel="stylesheet">
</head>

<style>
    /* button*/
    .btn {
        --color: #002795;
        --hover-color: #ffffff;
        padding: 0.5em 1em;
        /* kích thước padding */
        background-color: transparent;
        border-radius: 6px;
        border: 0.2px solid var(--color);
        /* độ dày border */
        transition: .5s;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        z-index: 1;
        font-weight: 500;
        font-size: 15px;
        /* kích thước font */
        font-family: Georgia, 'Times New Roman', Times, serif text-transform: uppercase;
        color: var(--color);
    }

    .btn::after,
    .btn::before {
        content: '';
        display: block;
        height: 100%;
        width: 100%;
        transform: skew(90deg) translate(-50%, -50%);
        position: absolute;
        inset: 50%;
        left: 25%;
        z-index: -1;
        transition: .5s ease-out;
        background-color: var(--color);
    }

    .btn::before {
        top: -50%;
        left: -25%;
        transform: skew(90deg) rotate(180deg) translate(-50%, -50%);
    }

    .btn:hover::before {
        transform: skew(45deg) rotate(180deg) translate(-50%, -50%);
    }

    .btn:hover::after {
        transform: skew(45deg) translate(-50%, -50%);
    }

    .btn:hover {
        color: var(--hover-color);
        background-color: var(--color);
        border-color: var(--color);
    }

    .btn:active {
        filter: brightness(.7);
        transform: scale(.98);
    }
</style>