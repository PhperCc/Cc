
String.prototype.ltrim=function(chars){ return this.replace(new RegExp('^' + (chars == null ? '\\s' : chars) + "+","g"),''); }
String.prototype.rtrim=function(chars){ return this.replace(new RegExp((chars == null ? '\\s' : chars) + "+$","g"),''); }
String.prototype.trim=function(chars){ return this.ltrim(chars).rtrim(chars); }
String.prototype.left = function(n){return this.length > n ? this.substr(0, n) : this}
String.prototype.right = function(n){return this.length > n ? this.substr(this.length - n, n) : this}
String.prototype.repeat = function(n){return new Array(n + 1).join(this);}
String.prototype.padleft = function(n, c){if(!c) c = " ";return this.length > n ? this.right(n) : c.repeat(n - this.length) + this}
String.prototype.padright = function(n, c){if(!c) c = " ";return this.length > n ? this.left(n) : this + c.repeat(n - this.length)}
String.prototype.startsWith = function(str){return this.length < str.length ? false : this.substr(0, str.length) == str;}
String.prototype.endsWith = function(str){return this.length < str.length ? false : this.substr(this.length - str.length) == str;}

function bytes2text($bytes)
{
    var $units = ['byte', 'KB', 'MB', 'GB', 'TB'];
    var $unitIndex = 0;
    while($bytes > 1024 && $unitIndex < $units.length)
    {
        $bytes /= 1024;
        $unitIndex++;
    }

    return Math.round($bytes * 100) / 100 + $units[$unitIndex];
}

$.ajaxSetup({
    timeout: 10000,
    beforeSend: function(request){
        request.setRequestHeader("req-method", "ajax");
    },
    error: function(XMLHttpRequest, textStatus, errorThrown){
        var msg = "状态 : " + textStatus + "<br />";
        msg += "错误 : " + errorThrown + "<br />";
        msgAlert(msg, '请求错误!');
    },
    statusCode: {500: function(){
        msgAlert("服务器执行错误!", '请求错误!');
    }}
});

function ajax(data, url, method, fn)
{
	$.ajax({
		url: url,
		type: method,
		data: data,
		dataType: 'json',
		success: function (data)
		{
			if (data.result == -1)
			{
				msgAlert(data.msg, '请求失败!');
				return;
			}
			fn(data);
		}
	});
}

//将form转为AJAX提交
function ajaxSubmit(frm, fn)
{
	var dataPara = getFormJson(frm);
	$.ajax({
		url: frm.action,
		type: frm.method,
		data: dataPara,
		dataType: 'json',
		success: function (data)
		{
			if (data.result == -1)
			{
				msgAlert(data.msg, '提交失败!');
				return;
			}
			fn(data);
		}
	});
}

function deleteItem(control, id, successFn)
{
    ajax({id: id}, './' + control + '..delete_item', 'post', function (data)
    {
        if (data.result == -1)
        {
            alert(data.data);
            window.location.href = './';
            return;
        }

        $('#divMsgConfirm').modal('hide');
        if (data.result == 1)
        {
            if(typeof(successFn) == 'function')
            {
                successFn(data);
            }
            else
            {
                window.location.reload();
            }
        }
        else if (data.result == 0)
        {
            msgAlert(data.msg, '操作失败!');
        }
    });
}

function updateStatus(control, id, disabled)
{
	var url = './' + control + '..update_status?id=' + id + '&disabled=' + disabled;

	var set_status_enable_cmd  = "javascript:updateStatus('" + control + "', " + id + ", 0);";
	var set_status_disable_cmd = "javascript:updateStatus('" + control + "', " + id + ", 1);";

	$.ajax({
		url: url,
		type: 'post',
		dataType: 'json',
		success: function (data)
		{
			if (data.result == -1)
			{
				alert(data.data);
				window.location.href = './';
				return;
			}

			if (data.result == 1)
			{
				var id = data.data;
				if (disabled == 0)
				{
					$('#status_text_' + id).html('已启用');
					$('#status_link_' + id).html('禁用');
					$('#status_link_' + id).attr('href', set_status_disable_cmd);
					$('#status_link_' + id).css('color', 'red');
				}
				else
				{
					$('#status_text_' + id).html('已禁用');
					$('#status_link_' + id).html('启用');
					$('#status_link_' + id).attr('href', set_status_enable_cmd);
					$('#status_link_' + id).css('color', 'green');
				}
			}
			else if (data.result == 0)
			{
				msgAlert(data.msg, '操作失败!');
			}
		}
	});
}

function goPage(index)
{
    var curPage = parseInt($('#frmItem input[name=page]').val());
    if (index == -1)
    {
        curPage--;
    }
    else if (index == -2)
    {
        curPage++;
    }
    else
    {
        curPage = index;
    }

    $('#frmItem input[name=page]').val(curPage);

    $('#frmItem').submit();
}

//将form中的值转换为键值对。
function getFormJson(frm)
{
	var o = {};
	var a = $(frm).serializeArray();
	$.each(a, function ()
	{
		if (o[this.name] !== undefined)
		{
			if (!o[this.name].push)
			{
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else
		{
			o[this.name] = this.value || '';
		}
	});

	return o;
}

function msgAlert(msg, title)
{
	if (title)
	{
		$('#divMsgAlert h3').html(title);
	}
	$('#divMsgAlert span').html(msg);
	$('#divMsgAlert').modal();
}

!function ($)
{
	$(function ()
	{
		var $window = $(window);

		$('.my-tooltip').tooltip('hide');

		$('#frmChanagePassword').submit(function ()
		{
			var dataPara = {oldPassword:$(this).find("#oldPassword").val(),newPassword1:$(this).find("#newPassword1").val(),newPassword2:$(this).find("#newPassword2").val()}
			$.ajax({
				url: this.action,
				type: this.method,
				data: dataPara,
				dataType:'json',
				success: function (data)
				{
					if (data.result == 1)
					{
						$("#myModal").modal('hide');
						//$('#spanPwdSuccess span').html(data.msg);
						//$('#spanPwdSuccess').show();
						//$('#frmChanagePassword input[type="password"]').val('');
					}
					else
					{
						$('#spanPwdError span').html(data.msg);
						$('#spanPwdError').show();
					}
				}
			});
			return false;
		});

		$('.confirm').click(function ()
		{
			$('#divMsgConfirm span').html($(this).data('confirm'));
			$('#divMsgConfirm .btn-primary').attr('href', this.href);
			$('#divMsgConfirm').modal();

			return false;
		});
	});
} (window.jQuery);