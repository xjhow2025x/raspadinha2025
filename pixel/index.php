<?php
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Confirmando...</title>
</head>
<body style="background-color: gray;">
<script>
    const img = new Image();

    img.onload = () => {
        window.location.href = "/";
    };

    img.src = "https://www.facebook.com/tr?id=667794396310150&ev=PageView&noscript=1";

    setTimeout(() => {
        window.location.href = "/";
    }, 1800); 
</script>
</body>
</html>
