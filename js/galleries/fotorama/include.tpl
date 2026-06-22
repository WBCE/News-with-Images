<script src="<?php echo WB_URL ?>/modules/news_img/js/galleries/fotorama/fotorama.js"></script>
<link rel="stylesheet" href="<?php echo WB_URL ?>/modules/news_img/js/galleries/fotorama/fotorama.css" />
<script>
(function ($) {
    if (!$) { return; }
    // Fotorama baut eigene <img class="fotorama__img"> ohne alt-Attribut und nutzt
    // den Beschreibungstext (picdesc / [DESCRIPTION]) nur als Caption. Hier schreiben
    // wir den Caption-Text zusaetzlich ins alt der sichtbaren Bilder (SEO/A11y).
    $(function () {
        $('.fotorama').each(function () {
            var $f = $(this);
            function syncAlt() {
                var api = $f.data('fotorama');
                $f.find('.fotorama__stage__frame').each(function (i) {
                    var $img = $(this).find('.fotorama__img');
                    if (!$img.length || $img.attr('alt')) { return; }
                    var text = $.trim($(this).find('.fotorama__caption__wrap').text());
                    if (!text && api && api.data && api.data[i] && api.data[i].caption) {
                        text = $.trim($('<div>').html(api.data[i].caption).text());
                    }
                    if (text) { $img.attr('alt', text); }
                });
            }
            // Captions/Frames entstehen teils erst beim Laden bzw. Wechsel -> mehrfach binden.
            $f.on('fotorama:ready fotorama:load fotorama:show fotorama:showend', syncAlt);
            syncAlt();
        });
    });
})(window.jQuery);
</script>

