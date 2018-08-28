// 微信配置分享
document.write("<script type='text/javascript' src='http://res.wx.qq.com/open/js/jweixin-1.2.0.js'></script>");
var sharetitle, sharedescription, sharethumb, sharecontentUrl;
$.post("/api/index/getconfig.do", {}, function(ret) {
    var json = eval("(" + ret + ")");
    var json_ = eval("(" + json.share_value + ")");
    sharetitle = json_.title;
    sharedescription = json_.description;
    sharecontentUrl = json_.wxurl;
    if (json_.wxthumb.indexOf("http")) {
        sharethumb = location.href.split('/mobile')[0] + json_.wxthumb;
    } else {
        sharethumb = json_.wxthumb;
    }
    // alert(sharecontentUrl+'------------'+sharethumb);
});

$.post("/wechat/index/getocken", {
    url: location.href
}, function(ret) {
    console.log(ret);
    var data = eval("(" + ret + ")");
    var sharecontent = {
        title: sharetitle, // 分享标题
        desc: sharedescription, // 分享描述
        link: sharecontentUrl, // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
        imgUrl: sharethumb, // 分享图标
        success: function() {},
        cancel: function() {}
    }
    wx.config({
        debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来
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
        wx.onMenuShareAppMessage(sharecontent);//朋友
        wx.onMenuShareTimeline(sharecontent);//朋友圈
        wx.onMenuShareQQ(sharecontent);//QQ
        wx.onMenuShareQZone(sharecontent);//QQ空间
    });
});
// 微信配置分享


function openWin(name) {
    location.href = name + '_frm.html';
}

var u = navigator.userAgent;
var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端


function openFram(name) {
    api.parseTapmode();
    var header = $api.byId('aui-header');
    $api.fixStatusBar(header);
    var headerPos = $api.offset(header);
    $api.setStorage('headerPos', headerPos.h);
    var body_h = $api.offset($api.dom('body')).h;
    api.openFrame({
        name: '' + name + '',
        url: '' + name + '_frm.html',
        bounces: false,
        scrollToTop: true,
        vScrollBarEnabled: false,
        // scrollEnabled:false,
        rect: {
            x: 0,
            y: headerPos.h,
            w: 'auto',
            h: 'auto'
        }
    })
}

function closeWin(name) {
    if (name == 'undefined' || name == undefined || name == '') {
        api.closeWin();
        return;
    }
    api.closeWin({
        name: '' + name + ''
    });
}
var r = 60;
var int;

function clock() {
    if (r < 0) {
        int = window.clearInterval(int);
        $("#getsmscode").removeClass('gray');
        $("#getsmscode").text('获取');
        r = 60;
    } else {
        $("#getsmscode").text(r + 's');
        r--;
    }

}

function closeTowin() {
    // alert(1);
    api.closeToWin({
        name: 'root'
    });
}

function getPicture(id, type, name) {
    if (isWeiXin() && (isAndroid || isiOS)) {
        wx.chooseImage({
            count: 1, // 默认9
            sizeType: ['original', 'compressed'], // 可以指定是原图还是压缩图，默认二者都有
            sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
            success: function(res) {
                var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
                var localId = res.localIds[0];
                wx.getLocalImgData({
                    localId: localId, // 图片的localID
                    success: function(res) {
                        // alert(JSON.stringify(res))

                        var localData = res.localData; // localData是图片的base64数据，可以用img标签显示
                        if (!window.__wxjs_is_wkwebview) { // 如果不是是IOS，需要加上前缀
                            localData = 'data:image/jpeg;base64,' + localData;
                            uploadimgop(id, localData, localId);
                        } else {
                            uploadimgop(id, localData, 0, type);
                        }

                    }
                });
            }
        });
        return;
    }
    $("#file").data('id', id);
    $("#file").data('type', type);
    $("#file").data('name', name);
    $("#file").click();
    return;
};

// alert(2);

$("#file").change(function() {
    // alert(1);
    //获取文件  

    var id = $(this).data('id');
    var type = $(this).data('type');
    var name = $(this).data('name');
    // alert(name);
    // return;
    var file = $("#orderform").find("#file")[0].files[0];
    //创建读取文件的对象  
    if (!/image\/\w+/.test(file.type)) {
        alert("只能选择图片");
        return false;
    }
    var reader = new FileReader();
    //创建文件读取相关的变量  
    var imgFile;
    reader.readAsDataURL(file);
    //为文件读取成功设置事件  
    reader.onload = function(e) {
        // alert('文件读取完成');

        imgFile = e.target.result;
        //console.log(imgFile);
        uploadimgop(id, imgFile, 0, type);

        // $("#imgContent").attr('src', imgFile);




    };
    $("#file").val('');
});


//上传结果验证提示消息
function uploadresmsg(id) {
    var dialog = new auiDialog();
    dialog.alert({
        title: "验证失败",
        msg: '您上传的证件图片与您填写的信息不符!',
        buttons: ['重新上传', '重新填写']
    }, function(ret) {
        if (ret) {
            var index = ret.buttonIndex;
            $("#" + id + '_text').val(0);
            $("#" + id).css({
                "background-image": "none"
            }).removeClass('active');
            if (ret.buttonIndex == 2) {
                $("#first_input").show();
                $("#second_input").hide();
                $("#first_").removeClass('div-active');
                $("#second").removeClass('btn-active');
                $("#toptitle").text('信息填写');
                click_status--;
            }
        }
    })



};



// 打开门店详情
function openstore() {
    var agentid = $("#agentid").val();
    if (agentid == 0) {
        return;
    }
    $api.setStorage('agentid', $("#agentid").val());
    openWin('storedetail');

}
// 查看示例
function example(id, imgid) {
    $("#" + id).addClass('active').css({
        "background-image": "url('" + $("#" + imgid).attr('src') + "')"
    });

}

function isWeiXin() {
    var ua = window.navigator.userAgent.toLowerCase();
    //console.log(ua); //mozilla/5.0 (iphone; cpu iphone os 9_1 like mac os x) applewebkit/601.1.46 (khtml, like gecko)version/9.0 mobile/13b143 safari/601.1
    if (ua.match(/MicroMessenger/i) == 'micromessenger') {
        return true;
    } else {
        return false;
    }
}

function uploadimgop(id, imgFile, localId, type) {
    var img = new Image();
    var toast = new auiToast();
    if (localId == 0) {
        $("#" + id).addClass('active').css({
            "background-image": "url('" + imgFile + "')"
        });
    } else {
        $("#" + id).addClass('active').css({
            "background-image": "url('" + localId + "')"
        });
    }


    img.src = imgFile;
    img.onload = function() {
        var width = img.width,
            height = img.height;
        //计算缩放比例
        var rate = 1;
        if (width >= height) {
            if (width > 720) {
                rate = 720 / width;
            }
        } else {
            if (height > 720) {
                rate = 720 / height;
            }
        };
        img.width = width * rate;
        img.height = height * rate;
        //生成canvas
        var canvas = document.createElement("canvas");
        var ctx = canvas.getContext("2d");
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0, img.width, img.height);
        var base64 = canvas.toDataURL('image/jpeg', 0.9);
        var imgurl = base64;
        $("#" + id + '_').val(base64); //设置input图片路径参数
        // //console.log(base64);
        toast.loading({
            title: "正在验证",
            duration: 2000
        });
        switch (id) {
            case 'drivinglicensePicZ':
                toast.hide();
                $("#" + id + '_text').val(1);
                // if ($("#nonepai").text() === '未上牌') {
                //     $.post('/api/user/getcarcode.do', { //行驶证1
                //         body: imgurl
                //     }, function(res) {
                //         if (res) {
                //             toast.hide();
                //             var result_ = JSON.parse(res).result.words_result;
                //             if (result_['所有人'] == 'undefined' || result_['所有人'] == undefined) {
                //                 $("#" + id + '_text').val(0);
                //                 $("#" + id).css({
                //                     "background-image": "none"
                //                 }).removeClass('active');
                //                 alert('证件上传有误，请重新上传');
                //                 return;
                //             }
                //             if (result_['所有人'].words != $("input[name='car_name']").val() || result_['号牌号码'].words != $("input[name='car_license']").val()) {
                //                 uploadresmsg(id);
                //             } else {

                //                 $("#" + id + '_text').val(res);
                //             }
                //         }
                //     });
                // } else {
                //     $("#" + id + '_text').val('购车发票');
                //     toast.hide();
                // }
                break;
            case 'drivinglicensePicF':
                toast.hide();
                $("#" + id + '_text').val(1);
                break;
            case 'IDPicZ':
                toast.hide();
                $("#" + id + '_text').val(1);
                // alert(imgurl);
                // $.post('/api/user/getidcode.do', { //身份证正面
                //     body: imgurl
                // }, function(res) {
                //     if (res) {
                //         toast.hide();
                //         var result_ = JSON.parse(res).result.cardsinfo;
                //         if (result_ == '') {
                //             $("#" + id + '_text').val(0);
                //             $("#" + id).css({
                //                 "background-image": "none"
                //             }).removeClass('active');
                //             alert('证件上传有误，请重新上传');
                //             return;
                //         }
                //         if (result_[0].items[1].content != $("input[name='car_name']").val()) {
                //             uploadresmsg(id);
                //         } else {
                //             // console.log(res);
                //             if (type == "people") { //为人脸识别
                //                 $("#" + id + '_text').val(imgurl);
                //             } else {
                //                 $("#" + id + '_text').val(res);
                //             }
                //         }
                //     }
                // });
                break;
            case 'IDPicF':
                toast.hide();
                $("#" + id + '_text').val(1);
                break;
            case 'certificate':
                toast.hide();
                $("#" + id + '_').val(imgurl);
                break;
            case 'IDPicS':
                // $("#" + id + '_').val(imgurl);
                toast.hide();
                $("#" + id + '_text').val(imgurl);
                break;
            default:
        }
        return;
    }
}