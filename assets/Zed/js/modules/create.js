'use strict';

var main = require('./main');

document.addEventListener('DOMContentLoaded', function () {
    main.initProtocolTypeToggle([
        {id: 'cxml-config-section', type: 'cxml'},
        {id: 'oci-config-section', type: 'oci'}
    ]);
});
