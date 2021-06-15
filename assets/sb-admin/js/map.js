'use strict';
import L from 'leaflet';

require('leaflet-easybutton');
require('@ansur/leaflet-pulse-icon');
require('leaflet-ajax/dist/leaflet.ajax.min');
require('./Leaflet.Editable');

// install icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: "/bundles/llecrudit/leaflet/images/marker-icon-2x.png",
    iconUrl: '/bundles/llecrudit/leaflet/images/marker-icon.png',
    shadowUrl: '/bundles/llecrudit/leaflet/images/marker-shadow.png',
});

window.addEventListener('load', function () {

    document.querySelectorAll(".crudit-map").forEach(map_elem => {
        let center = [map_elem.dataset.lat, map_elem.dataset.lng]
        let editable = (map_elem.dataset.editable !== 'off')
        let map = L.map(map_elem.id, {
            editable: editable,
            center: center,
            zoom: map_elem.dataset.zoom
        });
        let marker = null;
        let geo = null;
        var overlay = {}

        if (map_elem.dataset.with_marker === "1") {
            marker = L.marker(center).addTo(map);
        }
        // geojson layers
        if (map_elem.dataset.geojsons) {
            let geojsons = JSON.parse(map_elem.dataset.geojsons);
            geojsons.forEach((g) => {
                console.log(g);
                var g_layer;
                if( g["icon"] ) {
                    var icon = L.icon(g["icon"]);
                    console.log(g["icon"]);
                    g_layer = new L.GeoJSON.AJAX(g["url"], {
                        pointToLayer: function (geoJsonPoint, latlng) {
                            return L.marker(latlng, {icon: icon}).bindPopup(
                                "<iframe src=\"" + g["popup_url"] + geoJsonPoint.id + "\"></iframe>"
                            ).openPopup();
                        }
                    });
                } else {
                    g_layer = new L.GeoJSON.AJAX(g["url"], {
                        onEachFeature(feature, layer) {
                            layer.bindPopup("<iframe src=\"" + g["popup_url"] + feature.id + "\"></iframe>").openPopup();
                        }
                    });
                }

                overlay[g['libelle']]= g_layer
                g_layer.addTo(map);
            })
        }

        if (map_elem.dataset.polyline) {
            let feat = JSON.parse(map_elem.dataset.polyline);
            geo = L.geoJSON(feat);
            geo.addTo(map);

        }

        if (editable) {
            L.easyButton('fa-edit', (btn, map) => {
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
                formData.append('value', JSON.stringify(geometry));
                fetch(url,
                    {
                        body: formData,
                        method: "post"
                    }
                );
            });
            // for marker
            /*map.on('editable:drawing:move', (e) => {
                console.log(e);
            });*/
            map.on('editable:drawing:end', (e) => {
                console.log("end", e);
            });
            map.on('editable:drawing:mouseup', (e) => {
                console.log("mouseup", e);
            });

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
            console.log(tabEl);
            tabEl.addEventListener('shown.bs.tab', function () {
                console.log("show tab");
                setTimeout(() => {
                    map.invalidateSize()
                }, 100);
            })
        });

        //sites_layer_poly.addTo(this.map);
        //sites_layer_point.addTo(this.map);
        //point_layer.addTo(this.map);

        /*
        // couche geojson
        let sites_layer_poly = new L.GeoJSON.AJAX("/api/site_collecte/poly.json", {
          onEachFeature(feature, layer) {
            layer.bindPopup(
                "<iframe src=\"/api/site_collecte/" + feature.id + "/popup\"></iframe>"
            ).openPopup();
          }
        });
        let sites_layer_point = new L.GeoJSON.AJAX("/api/site_collecte/points.json");

        var greenIcon = L.icon({
          iconUrl: '/img/icons/tree_outline.svg',
          iconSize: [32, 32] // size of the icon
        });

        let point_layer = new L.GeoJSON.AJAX("/api/point_collecte/points.json", {
          pointToLayer: function (geoJsonPoint, latlng) {
            return L.marker(latlng, {icon: greenIcon}).bindPopup(
                "<iframe src=\"/api/point_collecte/" + geoJsonPoint.id + "/popup\"></iframe>"
            ).openPopup();
          }
        });

        let commune_layer;
        if (communeId === undefined) {
          commune_layer = new L.GeoJSON.AJAX("/api/communes/poly.json");
        } else {
          commune_layer = new L.GeoJSON.AJAX("/api/communes/" + communeId);
        }
        */

    });

});
