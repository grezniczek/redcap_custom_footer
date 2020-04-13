// @ts-check
(function() {
    function customFooter() {
        // Injection
        var addFooter = true
        var injectFirst = true
        var injectSecond = true
        var title = 'Title'
        var text1 = ''
        var text2 = ''
        var prefix = 'prefix'
        var firstId = 'system'
        var secondId = 'project'
        // Add custom footer at the appropriate place.
        var west = document.querySelector('#west')
        var xpanels = west.querySelectorAll('div.x-panel')
        if (xpanels.length > 0) {
            // Data entry page.
            var wrapper = document.createElement('div')
            wrapper.classList.add('x-panel')
            wrapper.innerHTML = title + '<div class="x-panel-bwrap"><div class="x-panel-body"><div class="menubox"><div class="menubox">' + text1 + text2 + '</div></div></div></div>'
            var last = xpanels[xpanels.length - 1]
            last.after(wrapper)
        }
        else {
            // Any other page.
            // Is there a footer? Give other modules a chance to add one. 200ms should be enough.
            var timeout = document.querySelector('#footer') ? 200 : 0
            setTimeout(function() {
                if (addFooter && document.querySelector('#footer')) {
                    // Let's add our own footer to inside #pagecontainer.
                    var pageContainer = document.querySelector('#pagecontainer')
                    var footer = document.createElement('div')
                    footer.classList.add('d-sm-block', 'col-md-12')
                    footer.setAttribute('id', 'footer')
                    footer.setAttribute('aria-hidden', 'true')
                    footer.style.display = 'block'
                    footer.innerHTML = '<a href="https://projectredcap.org" tabindex="-1" target="_blank" style="margin-bottom: 10px; display: inline-block;">Powered by REDCap</a>'
                    pageContainer.append(footer)
                }
                var f = document.querySelector('#footer')
                if (f) {
                    f.classList.remove('hidden-xs', 'd-none')
                    f.style.display = 'block'
                    for (var i = 0; i < f.children.length; i++) {
                        f.children[i].style.marginBottom = '10px'
                        f.children[i].style.display = 'inline-block'
                    }
                    if (injectFirst) {
                        var div = document.createElement('div')
                        div.innerHTML = 
                    }
                }
                if (f.length == 1) {
                    f.removeClass('hidden-xs')
                    f.removeClass('d-none')
                    f.css('display', 'block')
                    f.children().css('margin-bottom', '10px')
                    f.children().css('display', 'inline-block')
                    if (injectFirst) f.append($('#' + prefix + '_' + firstId + '_footer'))
                    if (injectSecond) {
                        f.append($('#' + prefix + '_' + secondId + '_footer'))
                    }
                }
            }, timeout)
        }
    }
    if (document.readyState === 'complete' || (document.readyState !== 'loading' && !document.documentElement.doScroll)) {
        customFooter()
    }
    else {
        document.addEventListener('DOMContentLoaded', customFooter)
    }
})()