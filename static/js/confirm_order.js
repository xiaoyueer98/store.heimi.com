//购物车增加减少
var num = document.getElementById("num_val");
//var max_num = document.getElementById("max_num");
var oneprice = document.getElementById("price");
function qtyUpdate(kind){	
	var price = "";
	var price_o = "";
	var rmb = document.getElementById("rmb");
	var true_pric = document.getElementById("true_pric");
	var trad = document.getElementById("trad");
	var oldprice = document.getElementById("oldprice");
	var false_pric = document.getElementById("false_pric");
	var i = parseInt(num.value);
//	var max_i = parseInt(max_num.value);
	var uprice = parseFloat(oneprice.value);
	var	unitprice = uprice.toFixed(2);
	var olduprice = parseFloat(oldprice.value);
	var oldunitprice = olduprice.toFixed(2);
	var trad_f = parseFloat(trad.value);
	if(kind == "up"){
//		if(i<max_i)
//		{
			i++;
                        $('#btn_jian').removeAttr('disabled');
//		}else
//		{
//			alert('数量过大，请与客服联系');
//		}
	}else if(kind == "down"){
		if(i > 2){
			 i--;
		}else if(i ==2)
                {
                    i--;
                    $('#btn_jian').attr('disabled','disabled');
                }
	}else if(kind == "write"){

	}

	num.value = i;
	price = i * unitprice + trad_f;
	price_o = i * oldunitprice;	
	price = parseFloat(price)*100;	
	//alert(price);
	var price_str = price.toString();
	var coin_length = '';
	var coin_length = price_str.indexOf('.');
	if(coin_length !='-1')
	{
		price_str = price_str.substr(0,coin_length); 
	}
	var price_html = '';
	if(price_str.length==1)
	{
		price_html = '0.0' + price_str;
	}else if(price_str.length==2)
	{
		price_html = '0.' + price_str;
	}else
	{
		price_html = price_str.substr(0,price_str.length-2) + '.' + price_str.substr(price_str.length-2,2);
	}
	price_o = parseFloat(price_o)*100;
	var	price_o_str = price_o.toString();
	var coin_o_length = price_o_str.indexOf('.');
	if(coin_o_length !=-1)
	{
		price_o_str = price_o_str.substr(0,coin_o_length);
	}
	var price_o_html = '';
	if(price_o_str.length==1)
	{
		price_o_html = '0.0' + price_o_str;	
	}else if(price_o_str.length==2)
	{
		price_o_html = '0.' + price_o_str;	
	}else
	{
		price_o_html = price_o_str.substr(0,price_o_str.length-2) + '.' + price_o_str.substr(price_o_str.length-2,2);
	}
	//总价
	rmb.innerHTML="￥" + price_html;
	//实付款
	true_pric.innerHTML="￥" + price_html;
	
	//商品总额

	false_pric.innerHTML="￥" + price_o_html;
}

var btn_jian = document.getElementById("btn_jian");
var btn_jia = document.getElementById("btn_jia");

btn_jian.onclick = function(){

	qtyUpdate("down");
}

btn_jia.onclick = function(){
	qtyUpdate("up");
}

num.onblur = function (){
	qtyUpdate("write");
}

function pay() 
{
	var session_id = $('#sid').val();
	var goods_id = $('#goods_id').val();
	var num = $('#num_val').val();
	var buyer_name = $('#buyer_name').html();
        var addressId = $('#addressId').val();
	if(buyer_name!='' && buyer_name!=null && buyer_name!='undefine')
	{   
		$("#order_sc").show();
		$.ajax({
			url: "/api/order/pay",
			data: "session_id=" + session_id + "&goods_id=" + goods_id + "&num=" + num + "&addressId=" + addressId,
			type: "POST",
			dataType: "json",
			success: function (re) {
				$("#lodd").hide();
				var code = re['code'];
				if (code == '200') {
					//$("#order_sc").show();
					location.href = re['data'];
					//$("#lodd").hide();
				}else if(code == '500'){
					like_alert("参数异常");
					$("#order_sc").hide();
				}

			}
		});
	}else
	{
		like_alert('请先添加收货地址');
		$("#order_sc").hide();
	}
}

