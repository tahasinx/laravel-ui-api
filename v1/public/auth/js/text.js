setTimeout(() => {
    var wishText = 'multi color text with css'
    x = 0;
    typeWriter = setInterval(function () {
        document.querySelector('.h1').textContent += wishText[x];
        x++;
        if (x > wishText.length - 1) {
            clearInterval(typeWriter);
        }
    }, 150);
}, 0);