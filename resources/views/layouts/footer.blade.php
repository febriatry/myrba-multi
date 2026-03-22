</div>
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('mazer') }}/js/app.js"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<script>
    try {
        if (window.self !== window.top) {
            document.body.classList.add('embed');
        }
    } catch (e) {
        document.body.classList.add('embed');
    }
</script>
<script>
    (function () {
        var inIframe = false;
        try {
            inIframe = window.self !== window.top;
        } catch (e) {
            inIframe = true;
        }
        if (inIframe) {
            document.body.classList.add('embed');
        }
        var isApp = document.body.classList.contains('appview');
        var origin = window.location.origin;
        var mode = inIframe ? 'embed' : (isApp ? 'app' : null);
        if (!mode) return;
        var queryKey = mode === 'embed' ? 'embed' : 'app';
        var queryValue = '1';
        var links = document.querySelectorAll('a[href]');
        for (var i = 0; i < links.length; i++) {
            var a = links[i];
            var href = a.getAttribute('href');
            if (!href) continue;
            var hrefLower = href.toLowerCase();
            if (hrefLower.startsWith('#') || hrefLower.startsWith('javascript:') || hrefLower.startsWith('mailto:') || hrefLower.startsWith('tel:')) continue;
            var url;
            try {
                url = new URL(href, window.location.href);
            } catch (e) {
                continue;
            }
            if (url.origin !== origin) continue;
            if (url.searchParams.get(queryKey) !== queryValue) {
                url.searchParams.set(queryKey, queryValue);
            }
            var newHref = url.pathname + (url.search || '') + (url.hash || '');
            if (newHref !== href) {
                a.setAttribute('href', newHref);
            }
        }
    })();
</script>
<script>
    $(document).ready(function() {
        $('.js-example-basic-single').select2();
    });
</script>

@include('sweetalert::alert')
@stack('js')
</body>

</html>
