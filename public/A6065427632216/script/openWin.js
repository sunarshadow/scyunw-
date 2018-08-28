function openWin(name) {
    var delay = 0;
    if (api.systemType != 'ios') {
        delay = 300;
    }
    api.openWin({
        name: '' + name + '',
        url: '' + name + '_win.html',
        bounces: false,
        delay: delay,
        slidBackEnabled: false, //ios向右滑动返回上一页
        vScrollBarEnabled: false
    });
}

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
    $("#file").data('id', id);
    $("#file").data('type', type);
    $("#file").data('name', name);
    $("#file").click();
    return;
};



$("#file").change(function() {
    //获取文件  
    var toast = new auiToast();
    var id = $(this).data('id');
    var type = $(this).data('type');
    var name = $(this).data('name');
    // alert(name);
    // return;
    var file = $("#orderform").find("#file")[0].files[0];
    //创建读取文件的对象  
    var reader = new FileReader();
    //创建文件读取相关的变量  
    var imgFile;
    //为文件读取成功设置事件  
    reader.onload = function(e) {
        // alert('文件读取完成');
        imgFile = e.target.result;
        console.log(imgFile);
        // $("#imgContent").attr('src', imgFile);
        $("#" + id).addClass('active').css({
            "background-image": "url('" + imgFile + "')"
        });
        var imgurl = imgFile;
        $("#" + id + '_').val(imgurl); //设置input图片路径参数
        toast.loading({
            title: "正在验证",
            duration: 2000
        });
        switch (id) {
            case 'drivinglicensePicZ':
                if ($("#nonepai").text() === '未上牌') {
                    $.post($api.getStorage('serviceAddr') + '/api/user/getcarcode.do', { //行驶证1
                        body: imgurl
                    }, function(res) {
                        if (res) {
                            toast.hide();
                            var result_ = JSON.parse(res).result.words_result;
                            if (result_['所有人'] == 'undefined' || result_['所有人'] == undefined) {
                                $("#" + id + '_text').val(0);
                                $("#" + id).css({
                                    "background-image": "none"
                                }).removeClass('active');
                                alert('证件上传有误，请重新上传');
                                return;
                            }
                            if (result_['所有人'].words != $("input[name='car_name']").val() || result_['号牌号码'].words != $("input[name='car_license']").val()) {
                                uploadresmsg(id);
                            } else {

                                $("#" + id + '_text').val(res);
                            }
                        }
                    });
                } else {
                    $("#" + id + '_text').val('购车发票');
                    toast.hide();
                }
                break;
            case 'drivinglicensePicF':
                toast.hide();
                $("#" + id + '_text').val(1);
                break;
            case 'IDPicZ':
                // alert(imgurl);
                $.post($api.getStorage('serviceAddr') + '/api/user/getidcode.do', { //身份证正面
                    body: imgurl
                }, function(res) {
                    if (res) {
                        toast.hide();
                        var result_ = JSON.parse(res).result.cardsinfo;
                        if (result_ == '') {
                            $("#" + id + '_text').val(0);
                            $("#" + id).css({
                                "background-image": "none"
                            }).removeClass('active');
                            alert('证件上传有误，请重新上传');
                            return;
                        }
                        if (result_[0].items[1].content != $("input[name='car_name']").val()) {
                            uploadresmsg(id);
                        } else {
                            if (type == "people") { //为人脸识别
                                $("#" + id + '_text').val(imgurl);
                            } else {
                                $("#" + id + '_text').val(res);
                            }
                        }
                    }
                });
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
    };
    reader.readAsDataURL(file);
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