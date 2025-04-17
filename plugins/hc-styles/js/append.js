document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() { document.body.insertAdjacentHTML('beforeend', '<link rel="stylesheet" id="hc-styles-fix-highlight-css" href="https://hcommons.org/app/plugins/hc-styles/css/fix-for-highlight-bug.css" media="all" />'); }, 3000) });

(function () {
    console.log("Injecting CSS");
    const cssHref = 'https://hcommons.org/app/plugins/hc-styles/css/fix-for-highlight-bug.css';
    const selector = 'iframe[name="editor-canvas"]';

    function injectStylesheet(iframe) {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;

            const link = doc.createElement('link');
            link.rel = 'stylesheet';
            link.href = cssHref;
            link.type = 'text/css';
            link.media = 'all';

            doc.body.appendChild(link);
            console.log(doc);

        } catch (e) {
            console.error('Failed to inject CSS into iframe:', e);
        }
    }

    function waitForIframeAndInject() {
        const iframe = document.querySelector(selector);
        if (iframe && iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
            classes = iframe.contentDocument.body.classList;
            for (cls of classes) {
                console.log("Testing for: " + cls);
                if (cls == "block-editor-iframe__body") {
                    console.log("Found block-editor-iframe__body. Injecting.");
                    setTimeout(injectStylesheet(iframe), 500);
                    return
                }
            }

            console.log("Waiting...");

            setTimeout(waitForIframeAndInject, 1000); // try again shortly


        } else {
            console.log("Waiting...");
            setTimeout(waitForIframeAndInject, 1000); // try again shortly
        }
    }

    window.addEventListener('load', waitForIframeAndInject);

})();
