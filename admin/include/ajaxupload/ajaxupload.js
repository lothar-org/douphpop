/**
 * WincomtechPHP
 * --------------------------------------------------------------------------------------------------
 * 版权所有 2013-2035 XXX网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.wowlothar.cn
 * --------------------------------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在遵守授权协议前提下对程序代码进行修改和使用；不允许对程序代码以任何形式任何目的的再发布。
 * 授权协议：http://www.wowlothar.cn/license.html
 * --------------------------------------------------------------------------------------------------
 * Author: Lothar
 * Release Date: 2015-10-16
 */

;(function($){

 var defaults = {
  ststem_max_file_size: '32768', // 服务器限制的上传文件大小，默认32M
  module: 'ohter',
  uploadform_id: 'uploadform',
  file: '#file',
  fileupload: '#fileupload',
  filename: 'filename',
  max_file_size: '1024000', // 单位kb
  progress_bar: '.progressBar', 
  bar: '.bar', 
  percent: '.percent', 
  cue: '.cue', 
  btn_upload: '.btnUpload em'
 }

 $.fn.ajaxupload = function(options){
  var settings = $.extend({}, defaults, options); // 用户定义参数替换默认参数
  
  // 格式化元素定位
  settings.url = 'include/ajaxupload/action.php?module=' + settings.module + '&filename=' + settings.filename;
  settings.file = $(settings.file, this);
  settings.fileupload = $(settings.fileupload, this);
  settings.progress_bar = $(settings.progress_bar, this);
  settings.bar = $(settings.bar, this);
  settings.percent = $(settings.percent, this);
  settings.cue = $(settings.cue, this);
  settings.btn_upload = $(settings.btn_upload, this);
  
  // 上传文件大小不能超过服务器限制
  if (settings.max_file_size > settings.ststem_max_file_size) {
       settings.max_file_size = settings.ststem_max_file_size;
       var msg_max_file_size = '文件大小超过限制，服务器限制大小为';
  } else {
       var msg_max_file_size = '文件大小超过限制，网站限制大小为';
  }
  
  settings.fileupload.wrap("<form id='" + settings.uploadform_id + "' action='" + settings.url + "' method='post' enctype='multipart/form-data'></form>"); 
  settings.fileupload.change(function(){ //选择文件
      // 文件类型和大小限制
      var filepath = settings.fileupload.val();
      var extStart = filepath.lastIndexOf(".");
      var ext = filepath.substring(extStart, filepath.length).toUpperCase();
      if (ext != ".FLV" && ext != ".SWF" && ext != ".AVI" && ext != ".MP4" && ext != ".WMV") {
          settings.cue.html("允许上传的视频格式：flv、swf、avi、mp4、wmv，建议使用flv！");
          settings.fileupload.val(''); // 清空文件域
          return false;
      }
      var file_size = 0;
      var isIE = /msie/.test(navigator.userAgent.toLowerCase());
      if (isIE) {
          var img = new Image();
          img.src = filepath;
          while (true) {
              if (img.fileSize > 0) {
                  if (img.fileSize > settings.max_file_size * 1024) {
                      settings.cue.html(msg_max_file_size + settings.max_file_size / 1024 + 'M。');
                      settings.fileupload.val(''); // 清空文件域
                  } else {
                      var ifupload = true;
                  }
                  break;
              }
          }
      } else {
          file_size = this.files[0].size;
          var size = file_size / 1024;
          if (size > settings.max_file_size) {
              settings.cue.html(msg_max_file_size + settings.max_file_size / 1024 + 'M。');
              settings.fileupload.val(''); // 清空文件域
          } else {
              var ifupload = true;
          }
      }
  
      // 上传操作（验证完后文件类型和大小后进入）
      if (ifupload) {
          $('#' + settings.uploadform_id).ajaxSubmit({ 
             dataType:  'json', //数据格式为json 
             beforeSend: function() { //开始上传 
                 settings.progress_bar.show(); //显示进度条 
                 var percentVal = '0%'; //开始进度为0% 
                 settings.bar.width(percentVal); //进度条的宽度 
                 settings.percent.html(percentVal); //显示进度为0% 
                 settings.btn_upload.html("上传中..."); //上传按钮显示上传中 
             }, 
             uploadProgress: function(event, position, total, percentComplete) { 
                 var percentVal = percentComplete + '%'; //获得进度 
                 settings.bar.width(percentVal); //上传进度条宽度变宽 
                 settings.percent.html(percentVal); //显示上传进度百分比 
             }, 
             success: function(data) { //成功 
                 //获得后台返回的json数据，显示文件名，大小，以及删除按钮 
                 settings.cue.html('成功上传文件，文件大小为：' + data.size + 'k'); 
                 settings.file.val(data.file); 
                 settings.fileupload.val(''); // 清空文件域
                 settings.progress_bar.hide(); // 清空上传进度 
                 settings.btn_upload.html('上传本地文件'); //上传按钮还原 
             }, 
             error:function(xhr){ //上传失败 
                 settings.btn_upload.html('上传失败'); 
                 settings.bar.width('0'); 
                 settings.progress_bar.hide(); // 清空上传进度 
                 settings.cue.html(xhr.responseText); //返回失败信息 
             } 
         }); 
      } // end ifupload
  });
  
  // returns the current jQuery object
  return this;
 }

})(jQuery);
