function  checkPostcode(val) {

    var res = /^[0-9]{6}$/;
    if (!res.test(val)) {
        return false;
    } else {
        return true;
    }
}
function checkMobile(val) {
    var reg = /^1[3|4|5|8][0-9]\d{8}$/;
    if (!(reg.test(val))) {
        return false;
    } else {
        return true;
    }

}
function stripscript(s) {
    var pattern = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？\"]");
    var rs = "";
    for (var i = 0; i < s.length; i++) {
        rs = rs + s.substr(i, 1).replace(pattern, '');
    }
    return rs;
}