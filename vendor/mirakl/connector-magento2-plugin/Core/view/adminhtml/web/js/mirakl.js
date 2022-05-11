require(['jquery'], function ($) {
    $(document).ready(function () {
        $('.catalog-product-edit .admin__page-nav-link.user-defined > span:first-child').each(function () {
            if (0 === $(this).text().indexOf('Mirakl')) {
                $(this).parents('li').addClass('mirakl');
            }
        });
        $('.adminhtml-system-config-edit .admin__page-nav-title > strong').each(function () {
            if (0 === $(this).text().indexOf('Mirakl')) {
                $(this).parent().parent().addClass('mirakl');
            }
        });
    });
});