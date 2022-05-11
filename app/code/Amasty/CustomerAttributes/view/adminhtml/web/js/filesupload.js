define([
    "jquery"
], function($) {
    function main(data) {
        $(document).on('change', 'input:file', function (event) {
            var formData = new FormData(),
                requestUrl = data.baseUrl;
            formData.append(event.target.name, event.target.files[0]);
            formData.append('name', event.target.name);
            formData.append('form_key', data.form_key);
            $.ajax({
                showLoader: true,
                url: requestUrl,
                processData: false,
                contentType: false,
                data: formData,
                dataType: 'text',
                method: "POST"
            })  .success(function (result) {
                var data = JSON.parse(result),
                    field = $("[name='am_file[" + data.name + "]']");

                if (field.length === 0) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'am_file[' + data.name + ']'
                    }).val(data.path).appendTo('form');
                }
                delete(formData);
            });
        });
    }

    return main;
});