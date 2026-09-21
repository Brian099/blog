/**
 * Stub ZeroClipboard implementation for modern browsers
 */
(function() {
    if (!window.ZeroClipboard) {
        window.ZeroClipboard = function() {
            return {
                on: function() { return this; },
                off: function() { return this; },
                clip: function() { return this; },
                unclip: function() { return this; },
                destroy: function() {},
                setText: function() {}
            };
        };
        window.ZeroClipboard.config = function() {};
    }
})();
