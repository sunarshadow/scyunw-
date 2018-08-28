// 定位
var map, geolocation;
//加载地图，调用浏览器定位服务
map = new AMap.Map('container', {
    resizeEnable: true
});
map.plugin('AMap.Geolocation', function() {
    geolocation = new AMap.Geolocation({
        enableHighAccuracy: true, //是否使用高精度定位，默认:true
        timeout: 10000, //超过10秒后停止定位，默认：无穷大
        buttonOffset: new AMap.Pixel(10, 20), //定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
        zoomToAccuracy: true, //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
        buttonPosition: 'LB',
        useNative: true
    });
    map.addControl(geolocation);
    geolocation.getCurrentPosition();
    AMap.event.addListener(geolocation, 'complete', onComplete); //返回定位信息
    AMap.event.addListener(geolocation, 'error', onError); //返回定位出错信息
});
//解析定位结果
function onComplete(data) {
    var marker = new AMap.Marker({
        icon: "http://webapi.amap.com/theme/v1.3/markers/n/mark_b.png",
        position: [data.position.getLng(), data.position.getLat()]
    });
    marker.setMap(map);
    map.getCity(function(data) {
        var address = data.province + '' + data.city + '' + data.district;
        $("#province").text(data.province);
        $("#city").text(data.city);
        $("#cityval").val(data.city);
        $("#province_").val(data.province);
        $("#city_").val(data.city);
    })
}
//解析定位错误信息
function onError(data) {
    alert('定位失败,请手动选择城市!');
}