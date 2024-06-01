document.addEventListener("DOMContentLoaded", function() {
    if (ar_settings.api_key) {
        Radar.initialize(ar_settings.api_key);

        function initializeAutocomplete(field_ids) {
            field_ids.forEach(function(field_id) {
                var originalFieldId = field_id.trim();
                var sanitizedFieldId = originalFieldId.replace(/[^a-zA-Z0-9_]/g, '_');
                var field = document.getElementById(originalFieldId);
                if (field) {
                    var dataListId = sanitizedFieldId + '-suggestions';
                    var dataList = document.getElementById(dataListId);
                    if (!dataList) {
                        dataList = document.createElement('datalist');
                        dataList.id = dataListId;
                        document.body.appendChild(dataList);
                        field.setAttribute('list', dataListId);
                    }

                    field.addEventListener('input', function(event) {
                        const query = event.target.value;
                        if (query.length > 2) {
                            Radar.autocomplete({ query: query, limit: 5 }).then((result) => {
                                const suggestions = result.addresses;
                                dataList.innerHTML = '';
                                suggestions.forEach((address) => {
                                    const option = document.createElement('option');
                                    option.value = address.formattedAddress;
                                    dataList.appendChild(option);
                                });
                            }).catch((err) => {
                                console.error(err);
                            });
                        }
                    });
                }
            });
        }

        if (ar_settings.frontend_field_ids && ar_settings.frontend_field_ids.length > 0) {
            initializeAutocomplete(ar_settings.frontend_field_ids);
        }

        if (ar_settings.backend_field_ids && ar_settings.backend_field_ids.length > 0) {
            initializeAutocomplete(ar_settings.backend_field_ids);
        }
    }
});
