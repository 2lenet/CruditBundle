'use strict';
import L from 'leaflet';

require('leaflet-easybutton');
require('leaflet-ajax/dist/leaflet.ajax.min');
require('./Leaflet.Editable');

// install icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: "/bundles/llecrudit/leaflet/images/marker-icon-2x.png",
    iconUrl: '/bundles/llecrudit/leaflet/images/marker-icon.png',
    shadowUrl: '/bundles/llecrudit/leaflet/images/marker-shadow.png',
});
//let markers = {}
window.addEventListener('load', function () {

    document.querySelectorAll(".crudit-map").forEach(map_elem => {
        let center = [map_elem.dataset.lat, map_elem.dataset.lng];
        let editable = (map_elem.dataset.editable !== undefined && map_elem.dataset.editable !== "off");
        let map = L.map(map_elem.id, {
            editable: editable,
            center: center,
            zoom: map_elem.dataset.zoom
        });
        let marker = null;
        let geo = null;
        var overlay = {};
        let fitbound = null;

        if (map_elem.dataset.with_marker === "1") {
            marker = L.marker(center).addTo(map);
        }
        // geojson layers
        if (map_elem.dataset.geojsons) {
            let geojsons = JSON.parse(map_elem.dataset.geojsons);
            geojsons.forEach((g) => {
                var g_layer;
                if( g["icon"] ) {
                    g_layer = new L.GeoJSON.AJAX(g["url"], {
                        pointToLayer: function (geoJsonPoint, latlng) {
                            g["icon"]["className"] = "mk-"+geoJsonPoint.id;
                            var icon = L.icon(g["icon"]);
                            console.log(icon);
                            return L.marker(latlng, {icon: icon, title: geoJsonPoint.title})
                                .bindPopup(
                                "<iframe height=\"400px\" src=\"" + g["popup_url"] + geoJsonPoint.id + "\"></iframe>"
                            ).openPopup();
                        }
                    });
                } else {
                    g_layer = new L.GeoJSON.AJAX(g["url"], {
                        onEachFeature(feature, layer) {
                            layer.bindPopup("<iframe height=\"400px\" src=\"" + g["popup_url"] + feature.id + "\"></iframe>").openPopup();
                        }
                    });
                }

                overlay[g['libelle']]= g_layer
                if (g["fitBounds"]) {
                    g_layer.on('data:loaded', function () {
                        fitbound = g_layer.getBounds();
                        map.fitBounds(g_layer.getBounds())
                    })
                }
                g_layer.addTo(map);
            })
        }

        if (map_elem.dataset.polyline) {
            let feat = JSON.parse(map_elem.dataset.polyline);
            geo = L.geoJSON(feat);
            geo.addTo(map);
        }
        L.easyButton('fa-map-marker', () => {
            if (fitbound) {
                map.fitBounds(fitbound,{animate:true});
            } else {
                map.flyTo(center);
            }
        }).addTo(map);

        if (editable) {
            L.easyButton('fa-edit', () => {
                if (geo) {
                    geo.getLayers().forEach(l => {
                        l.toggleEdit();
                        //l.setStyle({color: 'DarkRed'});
                    });
                }
                if (marker) {
                    marker.toggleEdit();
                }
            }).addTo(map);

            // for geojson
            map.on('editable:vertex:dragend', (event) => {
                // Note: the layer latlngs have been updated here but not the geojson geometry
                // copy changes to geojson
                let geometry = event.layer.toGeoJSON().geometry;
                let url = map_elem.dataset.edit_url;
                let formData = new FormData();

                let data = {
                    [map_elem.dataset.polyline_field]: JSON.stringify(geometry),
                };
                formData.append("data", JSON.stringify(data));
                fetch(url,
                    {
                        body: formData,
                        method: "post"
                    }
                );
            });

            // for marker
            if (marker) {
                marker.on('editable:dragend', (event) => {
                    let geometry = event.layer.toGeoJSON().geometry;

                    let url = map_elem.dataset.edit_url;
                    let formData = new FormData();

                    let data = {
                        [map_elem.dataset.lng_field]: geometry.coordinates[0],
                        [map_elem.dataset.lat_field]: geometry.coordinates[1],
                    }
                    formData.append("data", JSON.stringify(data));
                    fetch(url,
                        {
                            body: formData,
                            method: "post"
                        }
                    );
                });
            }
        }
        let osm = L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
        });
        let googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        });
        osm.addTo(map);
        var baseMaps = {
            "OpenStreetMap": osm,
            "Satellite": googleSat
        }
        L.control.layers(baseMaps, overlay).addTo(map);

        // if we are un a tab we need to recalculate size
        var tabEls = document.querySelectorAll('a[data-bs-toggle="tab"]')
        tabEls.forEach((tabEl) => {
            tabEl.addEventListener('shown.bs.tab', function () {
                setTimeout(() => {
                    map.invalidateSize()
                }, 100);
            })
        });

        // goto
        var gotoEls = document.querySelectorAll('[data-gotomap]');
        gotoEls.forEach((gotoElem) => {
            gotoElem.addEventListener('click', function () {
                let center = gotoElem.dataset.gotomap;
                let zoom = gotoElem.dataset.gotozoom;
                map.flyTo(center.split(","), zoom, {"duration":0.5});
            })
        });
        // goto marker_id

        var gotoMkEls = document.querySelectorAll('[data-gotomarker]');
        gotoMkEls.forEach((gotoElem) => {
            gotoElem.addEventListener('click', function () {
                let marker_id = gotoElem.dataset.gotomarker;
                var markers = document.querySelectorAll('img.leaflet-marker-icon');
                markers.forEach((mkIcon) => {
                    mkIcon.classList.remove('blinking');
                    if (mkIcon.classList.contains(marker_id)) {
                        mkIcon.classList.add('blinking');
                    }
                });
            })
        });
    });

});
