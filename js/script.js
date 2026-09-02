document.addEventListener("DOMContentLoaded", function () {

    const botoes = document.querySelectorAll(".answer");

    botoes.forEach(function (botao) {

        botao.addEventListener("click", function () {

            botoes.forEach(function (item) {
                item.style.borderColor = "#e4e9ec";
            });

            botao.style.borderColor = "#428f98";

        });

    });

});