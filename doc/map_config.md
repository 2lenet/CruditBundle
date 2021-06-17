# How to add a map to a list or to a show

You can easily add a map to a list or to a show.

**1. Declare your MapConfig**

If you want to add a map inside your list, just add this to your src/Crudit/Config/YourEntityCrudConfig.php

    public function getBrickConfigs(): array
    {
        $brickconfig = parent::getBrickConfigs();
        $indexBricks = [];

        $indexBricks[] = MapConfig::new();

        $brickconfig[CrudConfigInterface::INDEX] = $indexBricks;

        return $brickconfig;
    }

It is also possible to add a map to a tab:

    public function getTabs(): array
    {
        return [
           "yourentity.tab.map" => MapConfig::new()
        ];
    }

**2. Customise your map**

You can add these parameters inside MapConfig::new():

- zoom (default 6)
- lat (default 46)
- lng (default 2.5)
- geojsons (default null)
- editable (default false): allows you to move the marker directly on the map
- edit_route
- with_marker (default true)
- cssClass
- lat_field
- lng_field
- poly_field

Here is an example of a MapConfig with its options:
    
    MapConfig::new([
            "with_marker" => 0,
            "cssClass" => "col-8",
            "geojsons" => [
                [
                    "libelle" => "Site de collectes",
                    "url" => "/api/site_collecte/poly.json",
                    "popup_url" => "/api/site_collecte/popup/"
                ],
                [
                    "libelle" => "Points de collectes",
                    "url" => "/api/point_collecte/points.json",
                    "icon" => [
                        "iconUrl" => '/img/icons/tree_outline.svg',
                        "iconSize" => [32, 32] // size of the icon
                    ],
                    "popup_url" => "/api/point_collecte/popup/"
                ]
            ]
        ]);


