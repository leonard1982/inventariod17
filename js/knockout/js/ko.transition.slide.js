ko.bindingHandlers.slideTransition = {
    init: function (element, valueAccessor) {
        var data = ko.utils.unwrapObservable(valueAccessor());
        var visible = data;
        
        if (typeof data === 'object')
            visible = ko.utils.unwrapObservable(data.visible);

        $(element).toggle(visible);
    },
    update: function (element, valueAccessor) {
        var data = ko.utils.unwrapObservable(valueAccessor());

        var visible = data;
        var options;

        if (typeof data === 'object') {
            visible = ko.utils.unwrapObservable(data.visible);
            
            if (data.hasOwnProperty('options'))
                options = data.options;
        }
        
        if (visible) {
            $(element).stop(true, false).slideDown(options);
        } else {
            $(element).stop(true, false).slideUp(options);
        }
    }
};