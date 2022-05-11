requirejs([
    'jquery',
    'domReady!'
], function ($) {
    let sendGtag = function (action, category, label = '') {
        label = label.replaceAll("&amp;", "&");
        /*gtag('event', action, {
            'event_category': category,
            'event_label': label,
        });*/
        var dataObject = {
            'event': action,
            'category': category,
            'label': label
        };
        if(typeof window.dataLayer != 'undefined'){
            window.dataLayer.push(dataObject);
        }
    }

    /*On desktop events starts */
    let desktopAction = 'dt_main_menu_mouseover';
    let category = 'menu';
    let mainMenuSelect = '.sidebar-megamenu .megamenu-content-sidebar ';
    let mobileMainMenuSelect = '#navigation-mobile ';

    $(mainMenuSelect + '.btn-megamenu').mouseenter(function () {
        sendGtag('dt_sub_menu_mouseover', category);
    });

    /*Main Submenu */
    $(mainMenuSelect + '.mega-content .sm_megamenu_head.sm_megamenu_drop').on('click', function () {
        let label = $(this).find('.sm_megamenu_title').html();
        sendGtag('dt_menu_click', category, label);
        window.location.href = $(this).attr('href');
        event.preventDefault();
    });

    let sendTag = null;
    let sendTag2 = null;
    let sendTag3 = null;
    $(mainMenuSelect + '.sm_megamenu_head.sm_megamenu_drop').on("mouseover mouseout", function (event) {
        let self = $(this);

        if (event.type !== "mouseover" && sendTag != null) {
            clearTimeout(sendTag);
            sendTag = null;
        }

        if (event.type === "mouseover" && sendTag == null) {
            sendTag2 = null;
            sendTag3 = null;
            sendTag = setTimeout(function () {
                let label = self.find('.sm_megamenu_title').html();
                sendGtag(desktopAction, category, label);
            }, 3000);
        }
    });


    $(mainMenuSelect + '.mega-content .sm_megamenu_lv1.sm_megamenu_drop .sm_megamenu_col_2 ').each(function () {

        /*Child Submenu level 2*/
        $(this).find('.sm_megamenu_nodrop').first().on('click', function () {
            let firstlevelLabel = $(this).parents('.sm-megamenu-child').prev('.sm_megamenu_head.sm_megamenu_drop').find('.sm_megamenu_title').html();
            let secondLevelLabel = firstlevelLabel + "/" + $(this).find('.sm_megamenu_title_lv-2').html();
            sendGtag('dt_menu_click', category, secondLevelLabel);
            window.location.href = $(this).attr('href');
            event.preventDefault();
        });

        $(this).find('.sm_megamenu_nodrop').first().on("mouseover mouseout", function (event) {
            let self = $(this);

            if (event.type !== "mouseover" && sendTag2 != null) {
                clearTimeout(sendTag2);
                sendTag2 = null;
            }

            if (event.type === "mouseover" && sendTag2 == null) {
                sendTag = null;
                sendTag3 = null;
                sendTag2 = setTimeout(function () {
                    let firstlevelLabel = self.parents('.sm-megamenu-child').prev('.sm_megamenu_head.sm_megamenu_drop').find('.sm_megamenu_title').html();
                    let secondLevelLabel = firstlevelLabel + "/" + self.find('.sm_megamenu_title_lv-2').html();
                    sendGtag(desktopAction, category, secondLevelLabel);
                }, 3000);
            }
        });

        /*Child Submenu level 3*/
        $(this).find('.sm_megamenu_title .sm_megamenu_head_item .sm_megamenu_nodrop ').on('click', function () {
            let firstlevelLabel = $(this).parents('.sm-megamenu-child').prev('.sm_megamenu_head.sm_megamenu_drop').find('.sm_megamenu_title').html();
            let secondLevelLabel = $(this).parents('.sm_megamenu_head_item').find('.sm_megamenu_title_lv-2').html();
            let thirdLevelLabel = firstlevelLabel + "/" + secondLevelLabel + "/" + $(this).find('.sm_megamenu_title_lv-3').html();
            sendGtag('dt_menu_click', category, thirdLevelLabel);
            window.location.href = $(this).attr('href');
            event.preventDefault();
        });

        $(this).find('.sm_megamenu_title .sm_megamenu_head_item .sm_megamenu_nodrop ').on("mouseover mouseout", function (event) {
            let self = $(this);
            if (event.type !== "mouseover" && sendTag3 != null) {
                clearTimeout(sendTag3);
                sendTag3 = null;
            }

            if (event.type === "mouseover" && sendTag3 == null) {
                sendTag = null;
                sendTag2 = null;
                sendTag3 = setTimeout(function () {
                    let firstlevelLabel = self.parents('.sm-megamenu-child').prev('.sm_megamenu_head.sm_megamenu_drop').find('.sm_megamenu_title').html();
                    let secondLevelLabel = self.parents('.sm_megamenu_head_item').find('.sm_megamenu_title_lv-2').html();
                    let thirdLevelLabel = firstlevelLabel + "/" + secondLevelLabel + "/" + self.find('.sm_megamenu_title_lv-3').html();
                    sendGtag(desktopAction, category, thirdLevelLabel);
                }, 3000);
            }
        });
    });
    /*On desktop events ends */

    /*On mobile events starts */
    $('.btn-mobile #sidebar-button').on('touch click', function () {
        sendGtag('mobile_burger_tap', category);
    });

    $(mobileMainMenuSelect + 'li.other-toggle.sm_megamenu_lv1').on('touch click', function (event) {
        if ($(this).find('.parent-active')) {
            let label = $(this).find('.sm_megamenu_head.sm_megamenu_drop .sm_megamenu_title').html();
            sendGtag('mobile_sub_menu_expand', category, label);
        }
    });

    $(mobileMainMenuSelect + 'a.sm_megamenu_head.sm_megamenu_drop').on('click', function (event) {
        let label = $(this).find('.sm_megamenu_title').html();
        sendGtag('mobile_menu_tap', category, label);
        window.location.href = $(this).attr('href');
        event.preventDefault();
    });

    $(mobileMainMenuSelect + '.sm_megamenu_lv1.sm_megamenu_drop .sm_megamenu_col_2 ').each(function () {
        $(this).find('.sm_megamenu_nodrop').first().on('click', function () {
            let firstlevelLabel = $(this).parents('.sm-megamenu-child').prev('.sm_megamenu_head.sm_megamenu_drop').find('.sm_megamenu_title').html();
            let secondLevelLabel = firstlevelLabel + "/" + $(this).find('.sm_megamenu_title_lv-2').html();
            sendGtag('mobile_menu_tap', category, secondLevelLabel);
            window.location.href = $(this).attr('href');
            event.preventDefault();
        });
        $(this).find('.sm_megamenu_title .sm_megamenu_head_item .sm_megamenu_nodrop ').on('click', function () {
            let firstlevelLabel = $(this).parents('.sm-megamenu-child').prev('.sm_megamenu_head.sm_megamenu_drop').find('.sm_megamenu_title').html();
            let secondLevelLabel = $(this).parents('.sm_megamenu_head_item').prev('.sm_megamenu_nodrop ').find('.sm_megamenu_title_lv-2').html();
            let thirdLevelLabel = firstlevelLabel + "/" + secondLevelLabel + "/" + $(this).find('.sm_megamenu_title_lv-3').html();
            sendGtag('mobile_menu_tap', category, thirdLevelLabel);
            window.location.href = $(this).attr('href');
            event.preventDefault();
        });
    });
    /*On mobile events ends */

});
