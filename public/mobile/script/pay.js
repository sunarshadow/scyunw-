function getPay(id, fktype, qishu) {
    var actionsheet = new auiActionsheet();
    var posturl;
    if (qishu != 0) {
        posturl = $api.getStorage('serviceAddr') + "/api/order/payinstall.do";
    } else {
        posturl = $api.getStorage('serviceAddr') + "/api/order/buy.do";
    }
    actionsheet.init({
        frameBounces: true, //当前页面是否弹动，（主要针对安卓端）
        title: "请选择支付方式",
        cancelTitle: '取消',
        buttons: ['微信支付', '支付宝']
    }, function(retb, errb) {
        if (retb.buttonIndex == 3) {
            return;
        }
        var paytype = retb.buttonIndex;
        // alert(posturl+'--'+id+'--'+fktype+'--'+paytype+'--'+qishu);
        $.post(posturl, { id: id, fktype: fktype, paytype: paytype, qishu: qishu }, function(ret) {
            // alert(JSON.stringify(ret));
            var json = eval("(" + ret + ")");
            if (paytype == 1) {
                var prepayid = json.datas.prepayid;
                var partnerid = json.datas.partnerid;
                var noncestr = json.datas.noncestr;
                var timestamp = json.datas.timestamp;
                var sign = json.datas.sign;
                var tradeNO = json.tradeNO;
                wxPay(id, prepayid, partnerid, noncestr, timestamp, sign, tradeNO);
            } else {
                var subject = json.subject;
                var body = json.body;
                var amount = json.amount;
                var tradeNO = json.tradeNO;
                aliPay(id, subject, body, amount, tradeNO);
            }
        });
    });
}

function wxPay(id, prepayid, partnerid, noncestr, timestamp, sign, tradeNO) {
    var wxPay = api.require('wxPay');
    wxPay.payOrder({
        orderId: prepayid,
        mchId: partnerid,
        nonceStr: noncestr,
        timeStamp: timestamp,
        sign: sign
    }, function(ret, err) {
        if (ret.status) {
            alert('支付成功');
            openWin('mypolicy');
        } else {
            if (err.code = '-2') {
                alert('您取消了支付');
            }
        }
    });
}

function aliPay(id, subject, body, amount, tradeNO) {
    var aliPay = api.require('aliPay');
    aliPay.config({}, function(rett, errt) {
        aliPay.pay({
            subject: subject,
            body: body,
            amount: amount,
            tradeNO: tradeNO
        }, function(ret, err) {
            switch (ret.code) {
                case "9000":
                    alert('支付成功');
                    openWin('mypolicy');
                    break;
                case "6001":
                    alert('您取消了支付!');
                    break;
                default:
                    alert('系统异常,请联系客服');
                    break;
            }
        });
    });
}