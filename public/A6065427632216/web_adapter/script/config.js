/* 项目配置.基于默认配置,可以通过
 http://www.example.com/PUBLIC_PATH/web_adapter/adapter.html
访问自己的 app.如有修改,请对应变换访问地址即可.

服务器静态部署目录 PUBLIC_PATH,默认为用户 appId
*/

/* ==================== 用户相关配置.可根据需要,灵活修改. ============= */
/* app 入口文件. */
var APP_INDEX_PATH = 'html/index.html'

/* ====================== 以下适配器相关配置,一般不需要修改.=================== */
/* 适配器入口文件. */
var WEB_ADAPTER_INDEX_PATH = 'web_adapter/adapter.html'

/* 适配器核心js文件. */
var WEB_ADAPTER_CORE_JS_PATH = 'web_adapter/script/adapter.js'

/* =================== 应用和模块相关信息.一般由 APICloud 服务器自动生成.============= */
var PUBLIC_PATH = '/A6065427632216/'

var APP_INFO = {
  appId: 'A6065427632216',
  version: '1.2.23',
  appVersion: '00.00.01',
  appName: '共享车险'
}

var MODULES_INFO = [{
 "name":"aMap",
 "class":"GDMap",
 "methods":["open","takeSnapshotInRect","close","show","hide","setRect","getLocation","stopLocation","getCoordsFromName","getNameFromCoords","getDistance","showUserLocation","setTrackingMode","setCenter","getCenter","setZoomLevel","getZoomLevel","setMapAttr","setRotation","getRotation","setOverlook","getOverlook","setRegion","getRegion","setScaleBar","setCompass","setLogo","isPolygonContainsPoint","interconvertCoords","addEventListener","removeEventListener","addAnnotations","getAnnotationCoords","setAnnotationCoords","annotationExist","setBubble","popupBubble","closeBubble","addBillboard","addMobileAnnotations","moveAnnotation","removeAnnotations","addLine","addArc","addPolygon","addCircle","addImg","addLocus","removeOverlay","searchRoute","drawRoute","removeRoute","searchBusRoute","drawBusRoute","removeBusRoute","autocomplete","searchInCity","searchNearby","searchInPolygon","getProvinces","getMunicipalities","getNationWide","getAllCities","getVersion","downloadRegion","isDownloading","pauseDownload","cancelAllDownload","clearDisk","checkNewestVersion","reloadMap","setWebBubble","districtSearch"]
 }
,{
    "name":"mam",
    "class":"UZMAM",
    "methods":["checkUpdate","addEvent","execCommand","showToastAlert","hideToastAlert"],
    "syncMethods":["syncExecCommand"],
    "launchClassMethod":"launch"
}
]
