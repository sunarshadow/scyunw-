// 城市选择初始化
(function($, doc) {
    $.init();
    $.ready(function() {
        var cityPicker = new $.PopPicker({
            layer: 2
        });
        cityPicker.setData(cityData);
        var showCityPickerButton = doc.getElementById('position');
        showCityPickerButton.addEventListener('tap', function(event) {
            cityPicker.show(function(items) {
                document.getElementById("province").innerHTML = items[0].text;
                document.getElementById("province_").value = items[0].text;
                document.getElementById("city").innerHTML = items[1].text;
                document.getElementById("city_").value = items[1].text;
                document.getElementById("cityval").value = items[1].text;
            });
        }, false);
    });
})(mui, document);