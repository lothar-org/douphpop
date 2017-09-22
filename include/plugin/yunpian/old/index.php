<?php 
require_once '../../source/init_base.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>云片短信模拟</title>
    <script type="text/javascript" src="<?php echo OPJS ?>jquery-1.11.3.min.js"></script>
</head>
<body>
    <form action="msg.php" method="post">
        手机号：<input type="text" name="phone" id="phone">
        <input id="btnSendCode" type="button" value="发送验证码" onclick="sendMessage()" />
        <!-- <button id="btnSendCode" onclick="sendMessage()">发送验证码</button> -->
        <br>
        验证码：<input name="verify" required>
        <br>
        <input type="button" name="submit" value="提交">
    </form>

<script type="text/javascript">
var InterValObj; //timer变量，控制时间
var count = 5; //间隔函数，每1秒执行一次
var curCount;//当前剩余秒数
function sendMessage() {
    curCount = count;
    var phone = $('#phone').val();
    //设置button效果，开始计时
    $("#btnSendCode").attr("disabled",true);
    $("#btnSendCode").val("请在" + curCount + "秒内输入验证码");
    InterValObj = window.setInterval(SetRemainTime, 1000); //启动计时器，1秒执行一次
    //向后台发送处理数据
    $.ajax({
        type: 'POST', //用POST方式传输
        // dataType: 'text', //数据格式:默认text, 可选JSON、xml
        url: 'msg.php', //目标地址
        data: {'phone':phone,'type':2,'verify':'a1f8'},
        error: function (XMLHttpRequest, textStatus, errorThrown) { },
        success: function (data){
            console.log(data);
            // alert(data);
            // if (data) {
            //     alert('短信发送成功，请留意手机短信');
            // } else {
            //     alert('发送失败');
            // }
            // 如果是 json数组
            // if (data->code==0) {
            //     alert(data->msg)
            // } else {
            //     alert(data->msg)
            // }
        }
    });
}

//timer处理函数
function SetRemainTime() {
    if (curCount == 0) {                
        window.clearInterval(InterValObj);//停止计时器
        $("#btnSendCode").removeAttr("disabled");//启用按钮
        $("#btnSendCode").val("重新发送验证码");
    }
    else {
        curCount--;
        $("#btnSendCode").val("请在" + curCount + "秒内输入验证码");
    }
}
</script>
</body>
</html>