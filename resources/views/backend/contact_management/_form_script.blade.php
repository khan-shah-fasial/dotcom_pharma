<script>
    $(function () {
        var stateUrl = @json(route('get-state'));
        var optionsUrl = @json(route('contact-classifications.options'));
        var placeholderByKind = {
            category: @json(translate('Select Category')),
            subcategory: @json(translate('Select Subcategory')),
            type: @json(translate('Select Type')),
            subject: @json(translate('Select Subject')),
            work_profile: @json(translate('Select Work Profile'))
        };

        function refreshPicker($select) {
            $select.selectpicker('refresh');
        }

        function parseLocationOptions(response) {
            if (typeof response !== 'string') return response;
            try {
                return JSON.parse(response);
            } catch (error) {
                return response;
            }
        }

        function resetSelect($select, placeholder) {
            $select.html('<option value="">' + placeholder + '</option>');
            refreshPicker($select);
        }

        function clearDescendants($select) {
            var childSelector = $select.data('child');
            if (!childSelector) return;
            var $child = $(childSelector);
            resetSelect($child, placeholderByKind[$select.data('child-kind')] || '');
            clearDescendants($child);
        }

        function loadClassificationOptions($select, kind, parentId, selectedId) {
            var placeholder = placeholderByKind[kind] || '';
            resetSelect($select, placeholder);

            if (!parentId) {
                return $.Deferred().resolve().promise();
            }

            return $.getJSON(optionsUrl, { kind: kind, parent_id: parentId }).done(function (items) {
                $.each(items || [], function (index, item) {
                    $select.append($('<option>', { value: item.id, text: item.name }));
                });
                if (selectedId) {
                    $select.val(String(selectedId));
                }
                refreshPicker($select);
            });
        }

        $('.js-contact-cascade').on('change', function () {
            var $select = $(this);
            var childSelector = $select.data('child');
            var childKind = $select.data('child-kind');
            if (!childSelector || !childKind) return;

            var $child = $(childSelector);
            clearDescendants($select);
            loadClassificationOptions($child, childKind, this.value);
        });

        function loadStates(countryId, selectedStateId) {
            var $state = $('#contact_state_id');
            resetSelect($state, @json(translate('Select State')));
            if (!countryId) {
                return;
            }

            $.post(stateUrl, {
                _token: $('meta[name="csrf-token"]').attr('content'),
                country_id: countryId
            }).done(function (response) {
                $state.html(parseLocationOptions(response));
                if (selectedStateId) {
                    $state.val(String(selectedStateId));
                }
                refreshPicker($state);
            });
        }

        $('#contact_country_id').on('change', function () {
            loadStates(this.value);
        });

        if ($('#contact_country_id').val()) {
            loadStates($('#contact_country_id').val(), $('#contact_state_id').val());
        }

        $('#contact_add_social_media_row').on('click', function () {
            $('#contact_social_media_rows').append(
                '<div class="row gutters-5 contact-social-media-row mb-2">' +
                    '<div class="col-md-5"><input type="text" name="social_media_keys[]" class="form-control" placeholder="{{ translate('Platform') }}"></div>' +
                    '<div class="col-md-6"><input type="text" name="social_media_values[]" class="form-control" placeholder="{{ translate('ID / URL') }}"></div>' +
                    '<div class="col-md-1"><button type="button" class="btn btn-soft-danger btn-icon btn-circle js-remove-social-media-row" title="{{ translate('Remove') }}"><i class="las la-trash"></i></button></div>' +
                '</div>'
            );
        });

        $(document).on('click', '.js-remove-social-media-row', function () {
            var $rows = $('#contact_social_media_rows .contact-social-media-row');
            if ($rows.length <= 1) {
                $(this).closest('.contact-social-media-row').find('input').val('');
                return;
            }

            $(this).closest('.contact-social-media-row').remove();
        });
    });
</script>
