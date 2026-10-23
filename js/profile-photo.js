document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("foto_perfil");
    const dropzone = document.getElementById("photo-dropzone");
    const preview = document.getElementById("photo-preview");
    const filename = document.getElementById("selected-photo-name");
    const fontSize = document.getElementById("tamanho_letra");

    if (!input || !dropzone || !preview || !filename) {
        return;
    }

    let previewUrl = null;

    function showFile(file) {
        if (!file) {
            return;
        }

        if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
            filename.textContent = "Formato não aceito. Escolha JPG, PNG ou WEBP.";
            input.value = "";
            return;
        }

        if (file.size > 3 * 1024 * 1024) {
            filename.textContent = "A imagem deve ter no máximo 3 MB.";
            input.value = "";
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);
        preview.style.backgroundImage = "url(" + JSON.stringify(previewUrl) + ")";
        preview.classList.add("has-preview");
        filename.textContent = file.name;
    }

    input.addEventListener("change", function () {
        showFile(input.files[0]);
    });

    document.querySelectorAll(".font-size-step").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!fontSize) {
                return;
            }
            const values = Array.from(fontSize.options, function (option) {
                return Number(option.value);
            });
            const current = Number(fontSize.value);
            const requested = current + Number(button.dataset.step);
            const nearest = values.reduce(function (best, value) {
                return Math.abs(value - requested) < Math.abs(best - requested) ? value : best;
            }, values[0]);
            fontSize.value = String(nearest);
        });
    });

    ["dragenter", "dragover"].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.add("is-dragging");
        });
    });

    ["dragleave", "drop"].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.remove("is-dragging");
        });
    });

    dropzone.addEventListener("drop", function (event) {
        const file = event.dataTransfer.files[0];
        if (!file) {
            return;
        }
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        showFile(file);
    });

    window.addEventListener("beforeunload", function () {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
    });
});
