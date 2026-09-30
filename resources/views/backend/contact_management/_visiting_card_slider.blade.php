@if (!empty($visitingCardSlides))
<style>
    .js-contact-visiting-card { cursor: zoom-in; }
    .contact-card-viewer {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 100000;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.92);
        color: #fff;
    }
    .contact-card-viewer.is-open { display: flex; }
    .contact-card-viewer-image {
        max-width: calc(100% - 48px);
        max-height: calc(100% - 160px);
        object-fit: contain;
    }
    .contact-card-viewer-close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 44px;
        height: 44px;
        border: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        font-size: 28px;
        line-height: 44px;
    }
    .contact-card-viewer-title {
        position: absolute;
        top: 22px;
        left: 24px;
        right: 72px;
        margin: 0;
        font-size: 16px;
    }
    .contact-card-viewer-nav {
        position: absolute;
        bottom: 24px;
        width: 52px;
        height: 52px;
        border: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        font-size: 28px;
        line-height: 52px;
    }
    .contact-card-viewer-prev { left: 24px; }
    .contact-card-viewer-next { right: 24px; }
    .contact-card-viewer-nav[hidden] { display: none; }
</style>
<div class="contact-card-viewer" id="contactCardViewer" hidden>
    <p class="contact-card-viewer-title" id="contactCardViewerTitle"></p>
    <button type="button" class="contact-card-viewer-close" id="contactCardViewerClose" aria-label="{{ translate('Close') }}">&times;</button>
    <img class="contact-card-viewer-image" id="contactCardViewerImage" alt="">
    <button type="button" class="contact-card-viewer-nav contact-card-viewer-prev" id="contactCardViewerPrev" aria-label="{{ translate('Previous') }}">&#8249;</button>
    <button type="button" class="contact-card-viewer-nav contact-card-viewer-next" id="contactCardViewerNext" aria-label="{{ translate('Next') }}">&#8250;</button>
</div>
<script>
    (function () {
        var slides = @json($visitingCardSlides);
        var viewer = document.getElementById('contactCardViewer');
        var image = document.getElementById('contactCardViewerImage');
        var title = document.getElementById('contactCardViewerTitle');
        var prev = document.getElementById('contactCardViewerPrev');
        var next = document.getElementById('contactCardViewerNext');
        var current = 0;

        function show(index) {
            if (!slides.length) {
                return;
            }
            current = Math.max(0, Math.min(index, slides.length - 1));
            var slide = slides[current];
            image.src = slide.src;
            image.alt = slide.title || '';
            title.textContent = slide.title || '';
            prev.hidden = current === 0;
            next.hidden = current === slides.length - 1;
            viewer.hidden = false;
            viewer.classList.add('is-open');
            document.body.classList.add('hide-scrollbars');
        }

        function closeViewer() {
            viewer.classList.remove('is-open');
            viewer.hidden = true;
            image.removeAttribute('src');
            document.body.classList.remove('hide-scrollbars');
        }

        document.addEventListener('click', function (event) {
            var link = event.target.closest('.js-contact-visiting-card');
            if (!link) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            var contactId = String(link.getAttribute('data-contact-id'));
            var index = 0;
            for (var i = 0; i < slides.length; i++) {
                if (String(slides[i].contactId) === contactId) {
                    index = i;
                    break;
                }
            }
            show(index);
        }, true);

        document.getElementById('contactCardViewerClose').addEventListener('click', closeViewer);
        prev.addEventListener('click', function () { show(current - 1); });
        next.addEventListener('click', function () { show(current + 1); });
        viewer.addEventListener('click', function (event) {
            if (event.target === viewer) {
                closeViewer();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (viewer.hidden) {
                return;
            }
            if (event.key === 'Escape') {
                closeViewer();
            } else if (event.key === 'ArrowLeft' && current > 0) {
                show(current - 1);
            } else if (event.key === 'ArrowRight' && current < slides.length - 1) {
                show(current + 1);
            }
        });
    })();
</script>
@endif
