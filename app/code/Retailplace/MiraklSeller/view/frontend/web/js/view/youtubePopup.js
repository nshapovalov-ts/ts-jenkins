define([
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/modal'
], function ($, _, modal) {
    'use strict';

    return function (config) {
        let shopName = config.shopName,
            youTubeLink = config.youTubeLink;
        let options = {
            type: 'popup',
            responsive: true,
            title: shopName,
            modalVisibleClass: '_show youtubePopup',
            clickableOverlay: true,
            buttons: [{
                text: $.mage.__('Ok'),
                class: '',
                click: function () {
                    this.closeModal();
                }
            }]
        };
        let iframeScript = '<iframe id="youtube" width="100%" height="100%" src="' + youTubeLink + '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        $(".video-block").click(function() {
            $('#video').append(iframeScript)
            $('#modal').modal('openModal');
        });
        let modalPopup = $('#modal');
        var popup = modal(options, modalPopup);
        modalPopup.on('modalclosed', function() {
            $('#video').empty();
        });
    }
});
