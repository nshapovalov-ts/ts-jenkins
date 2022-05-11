<?php
/**
 * Retailplace_Xtento
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@vdcstore.com>
 */
namespace Retailplace\Xtento\Block\System\Config\Form\Xtento;

/**
 * Class Module
 */
class Module extends \Xtento\XtCore\Block\System\Config\Form\Xtento\Module
{
    protected function _getHeaderHtml($element)
    {
        $headerHtml = parent::_getHeaderHtml($element);
        $headerHtml = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $headerHtml);
        $scriptText = <<<EOD
 <script type="text/javascript">
                require(['jquery'], function($){
                    window.getXtentoLicenseKey = function() {
                        $.ajax({
                            dataType: "json",
                            url: "//www.xtento.com/license/info/?mode=retrieve&d="+$("input[id$='general_server']").val()
                        }).done(function(data) {
                            data.key = 'd9279601578cc3057df719384837ecc600e06650';
                            if (typeof data.key !== 'undefined' && data.key !== '') {
                                jQuery("input[id$='general_serial']").val(data.key);
                                jQuery("input[id$='general_serial']").next('p').hide().after('<p class="note" style="margin-top:2px;"><h5 style="color: green; font-weight: bold;">License key automatically retrieved, please enable module and save configuration.</h5></p>');
                            } else {
                                jQuery("input[id$='general_serial']").next('p').hide().after('<p class="note" style="margin-top:2px;"><h5 style="color: red; font-weight: bold;">License key could not be retrieved. Make sure this license key is registered with XTENTO. You need to see it at My Downloads on xtento.com in order to use the license key retrieval system here.</h5></p>');
                            }
                        }).fail(function(){
                            jQuery("input[id$='general_serial']").next('p').hide().after('<p class="note" style="margin-top:2px;"><h5 style="color: red; font-weight: bold;">License key could not be retrieved. Make sure this license key is registered with XTENTO. You need to see it at My Downloads on xtento.com in order to use the license key retrieval system here.</h5></p>');
                        });
                    };
                    $("input[id$='general_serial']").next('p').after('<button type="button" class="action-default scalable save primary ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" onclick="window.getXtentoLicenseKey()"><span class="ui-button-text"><span>Retrieve license key from XTENTO License Service</span></span></button>')
                });
                </script>
EOD;
        $headerHtml .= $scriptText;
        return $headerHtml;
    }
}
