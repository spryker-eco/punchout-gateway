'use strict';

/**
 * @param {Array<{id: string, type: string}>} sections
 */
function initProtocolTypeToggle(sections) {
    var sel = document.getElementById('punchout_connection_form_protocolType');
    var pluginSel = document.getElementById('punchout_connection_form_processorPluginClass');
    var els = sections.map(function (s) {
        return {el: document.getElementById(s.id), type: s.type};
    }).filter(function (s) { return s.el; });

    function updatePluginFilter() {
        if (!pluginSel) return;

        var type = sel ? sel.value : '';
        var currentValue = pluginSel.value;
        var hasMatch = false;

        for (var i = 0; i < pluginSel.options.length; i++) {
            var opt = pluginSel.options[i];
            var optType = opt.getAttribute('data-protocol-type') || '';
            var visible = optType === '' || optType === type;
            opt.hidden = !visible;
            opt.disabled = !visible;
            if (visible && opt.value === currentValue) hasMatch = true;
        }

        if (!hasMatch) pluginSel.value = '';
    }

    function updateVisibility() {
        var val = sel ? sel.value : '';
        els.forEach(function (s) {
            s.el.style.display = (val === s.type) ? '' : 'none';
        });
        updatePluginFilter();
    }

    if (sel) {
        sel.addEventListener('change', updateVisibility);
        updateVisibility();
    }
}

function initMappingRows(containerId, addBtnId, rowClass, removeBtnClass, onRowAdded) {
    var container = document.getElementById(containerId);
    var addBtn = document.getElementById(addBtnId);
    if (!container || !addBtn) return;

    var index = container.querySelectorAll('.' + rowClass).length;

    addBtn.addEventListener('click', function () {
        var prototype = container.getAttribute('data-prototype');
        var html = prototype.replace(/__name__/g, index);
        var div = document.createElement('div');
        div.className = rowClass + ' row';
        div.style.marginBottom = '4px';
        div.innerHTML = html;
        container.appendChild(div);
        if (onRowAdded) onRowAdded(div);
        index++;
    });

    container.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains(removeBtnClass)) {
            e.target.closest('.' + rowClass).remove();
        }
    });
}

module.exports = {initProtocolTypeToggle: initProtocolTypeToggle, initMappingRows: initMappingRows};
