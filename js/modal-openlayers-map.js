(function (global) {
    'use strict';

    let servicePromise;

    function init(configuration) {
        const mapElement = document.getElementById(configuration.mapId);

        if (!mapElement
            || !mapElement.closest('.c-modal')
            || mapElement.dataset.unibeMapInitialised === 'true') {
            return;
        }

        loadOpenLayers()
            .then(function () {
                initialiseWhenVisible(configuration, mapElement, 0);
            })
            .catch(function (error) {
                console.error('OpenLayers could not be loaded for the appointment modal.', error);
            });
    }

    function loadOpenLayers() {
        if (typeof global.initIlOpenLayerMaps === 'function') {
            return Promise.resolve();
        }

        if (servicePromise) {
            return servicePromise;
        }

        servicePromise = new Promise(function (resolve, reject) {
            const existingScript = document.querySelector('script[src*="ServiceOpenLayers.js"]');

            if (existingScript) {
                existingScript.addEventListener('load', resolve, {once: true});
                existingScript.addEventListener('error', reject, {once: true});
                return;
            }

            const script = document.createElement('script');
            script.src = 'assets/js/ServiceOpenLayers.js';
            script.addEventListener('load', resolve, {once: true});
            script.addEventListener('error', reject, {once: true});
            document.head.appendChild(script);
        });

        return servicePromise;
    }

    function initialiseWhenVisible(configuration, mapElement, attempt) {
        if (!mapElement.isConnected || attempt >= 60) {
            return;
        }

        if (mapElement.offsetWidth === 0 || mapElement.offsetHeight === 0) {
            global.requestAnimationFrame(function () {
                initialiseWhenVisible(configuration, mapElement, attempt + 1);
            });
            return;
        }

        const openLayer = global.initIlOpenLayerMaps(
            global.jQuery,
            configuration.invalidAddress,
            configuration.mapData,
            configuration.userMarkers
        );

        openLayer.forceResize(global.jQuery);
        openLayer.init(configuration.mapData);
        mapElement.dataset.unibeMapInitialised = 'true';
    }

    global.UnibeCalendarModalMap = {
        init: init
    };
}(window));
