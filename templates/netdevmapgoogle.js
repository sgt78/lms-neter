function initialize() 
{
if (GBrowserIsCompatible()) 
    {
    var mapOptions = 
	{
        googleBarOptions : 
	    {
	    style : "new"
	    }
	}
    map = new GMap2(document.getElementById("map"),mapOptions);
//    map.addControl(new GLargeMapControl());
    var MapTypes = map.getMapTypes();
    MapTypes[0].getName= function() { return "Mapa";}
    MapTypes[1].getName = function() { return "Satelitarna";}
    MapTypes[2].getName = function() { return "Hybrydowa";}
    map.addControl(new GMapTypeControl());
//    map.addControl(new GScaleControl());
    map.enableContinuousZoom();
    map.enableDoubleClickZoom();
    map.enableScrollWheelZoom();
    map.setCenter(new GLatLng(52.387296, 16.850774), 15);
    map.setMapType(G_HYBRID_MAP);
    var kml = new GGeoXml("http://maps.google.com/maps/ms?ie=UTF8&hl=en&msa=0&output=nl&msid=201050085797050147639.0004ac4829f5349b9a5e8");
    map.addOverlay(kml);
    map.setUIToDefault();
    map.enableGoogleBar();
    map.addControl(new GOverviewMapControl(new GSize(150,150)));
    }
}
