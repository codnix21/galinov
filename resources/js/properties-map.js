/**
 * Интерактивная карта объявлений (Яндекс.Карты API 2.1).
 */
(function () {
    const cfg = window.galinovMapConfig;
    if (!cfg?.apiKey) {
        return;
    }

    const script = document.createElement('script');
    script.src = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(cfg.apiKey)}&lang=ru_RU`;
    script.async = true;
    script.onload = initMap;
    document.head.appendChild(script);

    function initMap() {
        const el = document.getElementById('properties-map');
        if (!el || typeof ymaps === 'undefined') {
            return;
        }

        ymaps.ready(() => {
            const markers = cfg.markers || [];
            let center = cfg.defaultCenter;
            let zoom = cfg.defaultZoom;

            if (markers.length === 1) {
                center = [markers[0].lat, markers[0].lon];
                zoom = 14;
            } else if (markers.length > 1) {
                const lats = markers.map((m) => m.lat);
                const lons = markers.map((m) => m.lon);
                center = [
                    (Math.min(...lats) + Math.max(...lats)) / 2,
                    (Math.min(...lons) + Math.max(...lons)) / 2,
                ];
                zoom = 11;
            }

            const map = new ymaps.Map(el, {
                center,
                zoom,
                controls: ['zoomControl', 'fullscreenControl'],
            });

            const clusterer = new ymaps.Clusterer({
                preset: 'islands#invertedBlueClusterIcons',
                groupByCoordinates: false,
            });

            markers.forEach((m) => {
                const placemark = new ymaps.Placemark(
                    [m.lat, m.lon],
                    {
                        balloonContentHeader: m.title,
                        balloonContentBody: `<div>${m.type}<br>${m.city}<br><strong>${m.price}</strong><br><a href="${m.url}">Открыть</a></div>`,
                        hintContent: m.title,
                    },
                    { preset: 'islands#blueIcon' }
                );
                clusterer.add(placemark);
            });

            map.geoObjects.add(clusterer);

            if (markers.length > 1) {
                const bounds = clusterer.getBounds();
                if (bounds) {
                    map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 40 });
                }
            }
        });
    }
})();
