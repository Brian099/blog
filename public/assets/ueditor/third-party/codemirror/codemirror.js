/**
 * Stub CodeMirror implementation for UEditor fallback
 */
(function() {
    if (!window.CodeMirror) {
        window.CodeMirror = function(place, options) {
            var textarea = document.createElement('textarea');
            textarea.className = 'CodeMirror';
            if (options && options.value) textarea.value = options.value;
            if (typeof place === 'function') {
                place(textarea);
            } else if (place && place.appendChild) {
                place.appendChild(textarea);
            }
            return {
                getValue: function() { return textarea.value; },
                setValue: function(v) { textarea.value = v; },
                focus: function() { textarea.focus(); },
                getWrapperElement: function() { return textarea; },
                on: function() {},
                off: function() {}
            };
        };
        window.CodeMirror.fromTextArea = function(textarea, options) {
            return {
                getValue: function() { return textarea.value; },
                setValue: function(v) { textarea.value = v; },
                focus: function() { textarea.focus(); },
                getWrapperElement: function() { return textarea; },
                toTextArea: function() {},
                on: function() {},
                off: function() {}
            };
        };
    }
})();
