document.write("<script type='text/javascript' src='http://res.wx.qq.com/open/js/jweixin-1.2.0.js'></script>");
var sharetitle, sharedescription, sharethumb, sharecontentUrl;
$.post("/api/index/getconfig.do", {}, function(ret) {
    var json = eval("(" + ret + ")");
    var json_ = eval("(" + json.share_value + ")");
    sharetitle = json_.title;
    sharedescription = json_.description;
    sharecontentUrl = json_.url;
    if (json_.thumb.indexOf("http")) {
        sharethumb = $api.getStorage('serviceAddr') + json_.thumb;
    } else {
        sharethumb = json_.thumb;
    }
});

$.post("/wechat/index/getocken", {
    url: location.href
}, function(ret) {
    console.log(ret);
    var data = eval("(" + ret + ")");
    wx.config({
        debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来
        appId: data.appId, // 必填，公众号的唯一标识
        timestamp: data.timestamp, // 必填，生成签名的时间戳
        nonceStr: data.nonceStr, // 必填，生成签名的随机串
        signature: data.signature, // 必填，签名，见附录1
        jsApiList: data.jsApiList
    });
    wx.error(function(res) {
        // 
    });
    wx.ready(function(res) {
        wx.onMenuShareAppMessage({
            title: sharetitle, // 分享标题
            desc: sharedescription, // 分享描述
            link: 'http://chexian.302s.cn/mobile/image/logo.png', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
            imgUrl: "http://chexian.302s.cn/mobile/image/logo.png", // 分享图标
            success: function() {
                // 用户确认分享后执行的回调函数
            },
            cancel: function() {
                // 用户取消分享后执行的回调函数
            }
        });
    });
});