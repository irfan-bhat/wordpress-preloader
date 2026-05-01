/* WP Preloader v1.2 — admin JS — Irfan Bhat */
jQuery(function ($) {

    /* ── Media uploader ── */
    var frame;

    $('#lp-upload-btn').on('click', function () {
        if (frame) { frame.open(); return; }

        frame = wp.media({
            title:    'Choose Logo',
            button:   { text: 'Use this image' },
            multiple: false,
            library:  { type: 'image' },
        });

        frame.on('select', function () {
            var att = frame.state().get('selection').first().toJSON();
            $('#lp-logo-url').val(att.url).trigger('input');
            $('#lp-img-preview').attr('src', att.url);
            $('#lp-preview-wrap').show();
            $('#lp-remove-btn').show();
            syncPreviewLogo(att.url);
        });

        frame.open();
    });

    $('#lp-remove-btn').on('click', function () {
        $('#lp-logo-url').val('').trigger('input');
        $('#lp-img-preview').attr('src', '');
        $('#lp-preview-wrap').hide();
        $(this).hide();
        syncPreviewLogo('');
    });

    /* ── Helper: hex → r,g,b ── */
    function hexToRgb(hex) {
        var r = parseInt(hex.slice(1,3),16);
        var g = parseInt(hex.slice(3,5),16);
        var b = parseInt(hex.slice(5,7),16);
        return [r, g, b];
    }

    function rebuildOverlay() {
        var hex     = $('#lp-overlay-color').val();
        var opacity = parseInt($('#lp-overlay-opacity').val(), 10) / 100;
        var blur    = parseInt($('#lp-blur').val(), 10);
        var rgb     = hexToRgb(hex);
        var rgba    = 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + opacity.toFixed(2) + ')';
        var bFilter = 'blur(' + blur + 'px) saturate(1.4)';
        $('#lpv-overlay').css({
            'background':            rgba,
            'backdrop-filter':       bFilter,
            '-webkit-backdrop-filter': bFilter,
        });
    }

    /* ── Overlay color ── */
    $('#lp-overlay-color').on('input', function () {
        var hex = $(this).val();
        $(this).siblings('.lp-hex-text').val(hex);
        rebuildOverlay();
    });

    /* ── Accent color ── */
    $('#lp-accent').on('input', function () {
        var hex = $(this).val();
        $(this).siblings('.lp-hex-text').val(hex);
        $('#lpv-ring').css('border-top-color', hex);
        $('#lpv-fill').css('background', hex);
    });

    /* ── Hex text inputs ── */
    $('.lp-hex-text').on('input', function () {
        var val = $(this).val();
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            var target = $(this).data('for');
            $('#' + target).val(val).trigger('input');
        }
    });

    /* ── Blur strength slider ── */
    $('#lp-blur').on('input', function () {
        $('#lp-blur-val').text($(this).val() + 'px');
        rebuildOverlay();
    });

    /* ── Overlay opacity slider ── */
    $('#lp-overlay-opacity').on('input', function () {
        $('#lp-opacity-val').text($(this).val() + '%');
        rebuildOverlay();
    });

    /* ── Logo width ── */
    $('#lp-logo-width').on('input', function () {
        var w = parseInt($(this).val(), 10) || 64;
        $('#lpv-img').css('width', w + 'px');
        $('#lpv-wrap').css({ width: (w + 28) + 'px', height: (w + 28) + 'px' });
    });

    /* ── Toggle ring / bar ── */
    $('input[name$="[show_ring]"]').on('change', function () {
        $('#lpv-ring').toggle(this.checked);
    });
    $('input[name$="[show_bar]"]').on('change', function () {
        $('#lpv-bar').toggle(this.checked);
    });

    /* ── Logo URL typed manually ── */
    $('#lp-logo-url').on('input', function () {
        syncPreviewLogo($(this).val());
    });

    function syncPreviewLogo(url) {
        if (url) {
            $('#lpv-img').attr('src', url).show();
            $('#lpv-placeholder').hide();
        } else {
            $('#lpv-img').hide();
            $('#lpv-placeholder').show();
        }
    }
});
