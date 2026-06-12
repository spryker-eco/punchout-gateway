'use strict';

var main = require('./main');

function initAutocomplete(container) {
    container.querySelectorAll('.spryker-form-autocomplete').forEach(function (el) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.autocomplete) return;
        var $el = jQuery(el);
        if ($el.hasClass('ui-autocomplete-input')) {
            $el.autocomplete('destroy');
        }
        $el.autocomplete({source: el.getAttribute('data-url'), minLength: 3});
    });
}

document.addEventListener('DOMContentLoaded', function () {
    main.initProtocolTypeToggle([
        {id: 'cxml-config-section', type: 'cxml'},
        {id: 'oci-config-section', type: 'oci'},
        {id: 'cxml-mapping-section', type: 'cxml'},
        {id: 'oci-mapping-section', type: 'oci'}
    ]);
    initAutocomplete(document);
    main.initMappingRows('cxml-mapping-rows', 'js-add-mapping-row', 'cxml-mapping-row', 'js-remove-mapping-row', initAutocomplete);
    main.initMappingRows('cxml-extrinsic-rows', 'js-add-extrinsic-row', 'cxml-extrinsic-row', 'js-remove-extrinsic-row', initAutocomplete);
    main.initMappingRows('oci-mapping-rows', 'js-add-oci-mapping-row', 'oci-mapping-row', 'js-remove-oci-mapping-row', initAutocomplete);
});
