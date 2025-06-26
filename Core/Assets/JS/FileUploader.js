(function ($) {
    // Definimos el plugin fileUploader dentro del namespace de jQuery
    $.fn.fileUploader = function (options) {
        // Validamos que los parámetros obligatorios estén presentes
        if (!options || !options.url || !options.action || !options.chunkSize) {
            console.error("Error: Los parámetros 'url', 'action' y 'chunkSize' son obligatorios.");
            return this;
        }

        // Configuramos los ajustes del plugin, extendiendo las opciones predeterminadas con las proporcionadas por el usuario
        let settings = $.extend({
            url: options.url, // URL a la que se enviarán las peticiones de subida
            action: options.action, // Acción que se enviará junto con los datos del chunk
            chunkSize: options.chunkSize, // Tamaño del chunk en bytes
            onStart: function (filename, fileSize) {}, // Callback al iniciar la subida
            onProgress: function (percentage) {}, // Callback para actualizar el progreso
            onComplete: function (filename) {}, // Callback al completar la subida
            onError: function (error) {} // Callback en caso de error
        }, options);

        // Función para manejar la subida de archivos
        function uploadFile(file) {
            // Llamamos al callback onStart con el nombre y tamaño del archivo
            settings.onStart(file.name, file.size);

            // Calculamos el número total de chunks y la posición actual
            let totalChunks = Math.ceil(file.size / settings.chunkSize);
            let currentChunk = 0;

            // Función recursiva para subir cada chunk
            function uploadChunk() {
                // Si hemos subido todos los chunks, llamamos al callback onComplete
                if (currentChunk >= totalChunks) {
                    settings.onComplete(file.name);
                    return;
                }

                // Calculamos el inicio y el fin del chunk actual
                let start = currentChunk * settings.chunkSize;
                let end = Math.min(start + settings.chunkSize, file.size);
                let chunk = file.slice(start, end); // Obtenemos el chunk del archivo
                let formData = new FormData();

                // Añadimos los datos necesarios al FormData
                formData.append("action", settings.action);
                formData.append("file", chunk);
                formData.append("filename", file.name);
                formData.append("totalChunks", totalChunks);
                formData.append("chunkIndex", currentChunk);

                // Realizamos la petición AJAX para subir el chunk
                $.ajax({
                    url: settings.url,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function () {
                        currentChunk++; // Incrementamos el índice del chunk actual
                        let progress = Math.round((currentChunk / totalChunks) * 100); // Calculamos el progreso
                        settings.onProgress(progress); // Llamamos al callback onProgress
                        uploadChunk(); // Subimos el siguiente chunk
                    },
                    error: function (xhr, status, error) {
                        settings.onError(error); // Llamamos al callback onError en caso de fallo
                    }
                });
            }

            uploadChunk(); // Iniciamos la subida con el primer chunk
        }

        // Iteramos sobre cada elemento seleccionado y añadimos el evento change
        return this.each(function () {
            $(this).on("change", function (e) {
                let file = e.target.files[0]; // Obtenemos el primer archivo seleccionado

                if (!file) return; // Si no hay archivo, salimos de la función

                uploadFile(file); // Llamamos a la función uploadFile con el archivo seleccionado
            });
        });
    };
})(jQuery);