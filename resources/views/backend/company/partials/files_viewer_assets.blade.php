<div class="modal fade" id="companyFilesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companyFilesModalTitle">{{ translate('Certificates & Documents') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6 class="mb-2">{{ translate('Certificates') }}</h6>
                <div class="d-flex flex-wrap mb-3" id="companyFilesCertificates"></div>
                <h6 class="mb-2">{{ translate('Important Documents') }}</h6>
                <div class="d-flex flex-wrap" id="companyFilesDocuments"></div>
            </div>
        </div>
    </div>
</div>

<div id="companyFileSlide" class="company-file-slide d-none" role="dialog" aria-modal="true">
    <div class="company-file-slide-bar">
        <button type="button" class="btn btn-light btn-sm" data-slide="prev">{{ translate('Previous') }}</button>
        <button type="button" class="btn btn-light btn-sm" data-zoom="in">{{ translate('Zoom in') }}</button>
        <button type="button" class="btn btn-light btn-sm" data-zoom="out">{{ translate('Zoom out') }}</button>
        <button type="button" class="btn btn-light btn-sm" data-slide="close">{{ translate('Close') }}</button>
        <button type="button" class="btn btn-light btn-sm" data-slide="next">{{ translate('Next') }}</button>
    </div>
    <div class="company-file-slide-stage" id="companyFileSlideStage"></div>
    <div class="company-file-slide-caption" id="companyFileSlideCaption"></div>
</div>

<style>
    .company-file-thumb {
        width: 92px;
        margin: 0 8px 8px 0;
        text-align: center;
        cursor: pointer;
        border: 0;
        background: transparent;
        padding: 0;
    }
    .company-file-thumb .icon {
        width: 72px;
        height: 72px;
        border: 1px solid #e2e5ec;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #fff;
        margin: 0 auto 4px;
    }
    .company-file-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .company-file-thumb .name {
        font-size: 11px;
        line-height: 1.3;
        word-break: break-word;
    }
    .company-file-slide {
        position: fixed;
        inset: 0;
        z-index: 2000;
        background: rgba(0, 0, 0, 0.88);
        display: flex;
        flex-direction: column;
    }
    .company-file-slide-bar {
        display: flex;
        justify-content: center;
        gap: 8px;
        padding: 12px;
        flex-wrap: wrap;
    }
    .company-file-slide-stage {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
        padding: 12px;
    }
    .company-file-slide-stage img,
    .company-file-slide-stage iframe {
        max-width: 100%;
        max-height: 75vh;
        background: #fff;
        transform-origin: center center;
    }
    .company-file-slide-caption {
        color: #fff;
        text-align: center;
        padding: 8px 16px 16px;
    }
</style>

@push('company_scripts')
<script>
    $(function () {
        var slideGroup = [];
        var slideIndex = 0;
        var zoom = 1;

        function thumbHtml(item, group, index) {
            var icon = '<i class="las la-file la-2x"></i>';
            if (item.preview === 'image' && item.url) {
                icon = '<img src="' + $('<div>').text(item.url).html() + '" alt="">';
            } else if (item.preview === 'pdf') {
                icon = '<i class="las la-file-pdf la-2x"></i>';
            }
            var validity = item.valid_until
                ? '<div class="text-muted">' + $('<div>').text(item.valid_until).html() + '</div>'
                : '';
            return '<button type="button" class="company-file-thumb" data-group="' + group + '" data-index="' + index + '">' +
                '<span class="icon">' + icon + '</span>' +
                '<div class="name">' + $('<div>').text(item.name || '').html() + '</div>' +
                validity +
                '</button>';
        }

        function renderThumbs(target, items, group) {
            var $target = $(target);
            $target.empty();
            if (!items.length) {
                $target.append('<div class="text-muted mb-2">' + @json(translate('None uploaded.')) + '</div>');
                return;
            }
            items.forEach(function (item, index) {
                $target.append(thumbHtml(item, group, index));
            });
        }

        function showSlide() {
            var item = slideGroup[slideIndex];
            var $stage = $('#companyFileSlideStage');
            $stage.empty();
            if (!item) {
                return;
            }
            if (item.preview === 'image' && item.url) {
                $stage.append($('<img>', { src: item.url, alt: item.name || '' }).css('transform', 'scale(' + zoom + ')'));
            } else if (item.preview === 'pdf' && item.url) {
                $stage.append($('<iframe>', { src: item.url, title: item.name || 'PDF' }).css({ width: '900px', height: '640px' }));
            } else if (item.url) {
                $stage.append($('<a>', {
                    href: item.url,
                    target: '_blank',
                    rel: 'noopener',
                    class: 'btn btn-primary',
                    text: @json(translate('Open file'))
                }));
            }
            var caption = item.name || '';
            if (item.valid_until) {
                caption += ' · ' + item.valid_until;
            }
            caption += ' (' + (slideIndex + 1) + '/' + slideGroup.length + ')';
            $('#companyFileSlideCaption').text(caption);
            $('#companyFileSlide').removeClass('d-none');
        }

        $(document).on('click', '.js-open-company-files', function () {
            var payload = {};
            try {
                payload = JSON.parse($($(this).data('target')).text() || '{}');
            } catch (error) {
                payload = {};
            }
            $('#companyFilesModalTitle').text($(this).data('title') || @json(translate('Certificates & Documents')));
            window.companyFileGroups = {
                certificates: payload.certificates || [],
                documents: payload.documents || []
            };
            renderThumbs('#companyFilesCertificates', window.companyFileGroups.certificates, 'certificates');
            renderThumbs('#companyFilesDocuments', window.companyFileGroups.documents, 'documents');
            $('#companyFilesModal').modal('show');
        });

        $(document).on('click', '.company-file-thumb', function () {
            var group = $(this).data('group');
            slideGroup = (window.companyFileGroups && window.companyFileGroups[group]) || [];
            slideIndex = Number($(this).data('index')) || 0;
            zoom = 1;
            showSlide();
        });

        $('#companyFileSlide').on('click', '[data-slide]', function () {
            var action = $(this).data('slide');
            if (action === 'close') {
                $('#companyFileSlide').addClass('d-none');
                return;
            }
            if (!slideGroup.length) {
                return;
            }
            if (action === 'next') {
                slideIndex = (slideIndex + 1) % slideGroup.length;
            }
            if (action === 'prev') {
                slideIndex = (slideIndex - 1 + slideGroup.length) % slideGroup.length;
            }
            zoom = 1;
            showSlide();
        });

        $('#companyFileSlide').on('click', '[data-zoom]', function () {
            if (!slideGroup.length || slideGroup[slideIndex].preview !== 'image') {
                return;
            }
            zoom = $(this).data('zoom') === 'in' ? Math.min(zoom + 0.25, 3) : Math.max(zoom - 0.25, 1);
            $('#companyFileSlideStage img').css('transform', 'scale(' + zoom + ')');
        });
    });
</script>
@endpush
