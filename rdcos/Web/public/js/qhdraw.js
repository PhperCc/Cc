
var PAVER = 196865;

var Draw = function() {
	this.runType        = 'web';
	this.canvasMainJq   = null;
	this.canvasMain     = null;
	this.canvasCalc     = null;
	this.ctxMain        = null;
	this.ctxCalc        = null;
	this.w              = 0;
	this.h              = 0;
	this.zoom           = 10; // 1米多少像素
	this.zoomMax        = 200;
	this.zoomMin        = 8;
	this.rect           = [0,0,0,0];
	this.rectLine       = [0,0,0,0];
	this.center         = [0,0];
	this.isMove         = false;
	this.isPinch        = false;
	this.isBusy         = false;
	this.isInit         = false;
	this.curCar         = '';
	this.mapRatio       = 1;      // 维度经度比例
	this.mapMeter       = 111111; // 1纬度多少米
	this.showTypeList   = [];
	this.showTypeIndex  = 'run_line';
	this.layerColorList = [];
	this.isCenterRun    = true;
	this.isCenterRunTimeout = null;
	this.workareaList   = null;
    this.carDrawType    = 'html5'; // 绘制车辆方式 html5 / canvas
    this.drawPointList  = [];
	this.userColorList  = [];
	this.drawColorList  = [];
	this.drawCircleList = [];

	this.initWeb = function(xy, typeList, typeIndex)
	{
		this.runType = 'web';
		this.init(xy, typeList, typeIndex);
	}

	this.initClient = function(xy, typeList, typeIndex)
	{
		this.runType = 'client';
		this.init(xy, typeList, typeIndex);
	}
	
	this.typeReset = function(typeList, typeIndex)
	{
		typeIndex = typeIndex || this.showTypeIndex;
		
		$('#selShowType').html('');
		for (var i in typeList)
		{
			$('#selShowType').append('<option value="' + i + '">' + typeList[i].name + '</option>');
		}

		$('#selShowType').val(typeIndex);
		
		this.showTypeList  = typeList;
		this.showTypeIndex = typeIndex;
		this.showTypeChange();
	}

	this.init = function(xy, typeList, typeIndex)
	{
		this.typeReset(typeList, typeIndex);

		this.canvasMainJq  = $('#canvasMain');
		this.canvasMain = document.getElementById('canvasMain');
		this.canvasCalc = document.getElementById('canvasCalc');
		this.canvasCar = document.getElementById('canvasCar');
		this.canvastext = document.getElementById('canvastext');

		this.bindEvent();

		this.mapRatio = this.getMapRatio(xy[1]);
		this.center = xy;

		this.resize();
		
		this.isInit = true;
	}
	
	this.centerMove = function(xy)
	{
		if (this.isMove) return;
		if (this.isPinch) return;
		
        DS.isAdd = true;
		if (this.isCenterRun)
		{
            if (xy[0] < this.rect[0] || xy[0] > this.rect[2] || xy[1] < this.rect[1] ||  xy[1] > this.rect[3])
            {
                DS.isAdd = false;
                this.mapRatio = this.getMapRatio(xy[1]);
                this.center = xy;
            }
		}
		this.reset();
	}
	
	this.moveTo = function(xy)
	{
		if (!this.isInit) return;
		if (this.isBusy) return;
		
		this.mapRatio = this.getMapRatio(xy[1]);
		this.center = xy;
		this.reset();
	}

	this.showTypeChange = function()
	{
		var showType = this.showTypeList[this.showTypeIndex];
		
		$('#divLimit').hide();
		$('#divBianLimit').hide();
		if (showType.type == 'bian_qhj' && this.drawColorList.length > 0)
		{

			$('#divBianLimit .c').html('');
			if (showType.min <= this.drawColorList.length)
			{
				this.userColorList = this.drawColorList;
							
				for (var i=0; i<(showType.min - 1); i++)
				{
					var c = this.drawColorList[i];
					if (c.indexOf("#") === 0)
					{

					}
					else
					{
						
					}
					$('#divBianLimit .c').append('<span style="background-color:rgb(' + c.join(',') + ');">' + (i+1) + ' 遍</span>');
				}
				var c =  this.drawColorList[this.drawColorList.length - 1];
				$('#divBianLimit .c').append('<span style="background-color:rgb(' + c.join(',') + ');">合格</span>');
			}
			else
			{
				// // 超出最大合格遍数
				var over = showType.min - this.drawColorList.length;
				// 将超出遍数平均到前面一半的遍数内
				var step = Math.floor(over / Math.floor(this.drawColorList.length / 2)) + 1;
				
				this.userColorList = [];
				var j = 0;
				for (var i=0; i<(this.drawColorList.length - 1); i++)
				{
					// var c = this.drawColorList[i];
					var c =  this.drawColorList[this.drawColorList.length - 1];
					// 剩余的颜色数量
					var hasColorCount = this.drawColorList.length - 1 - i;
					var needColorCount =  showType.min - 1 - j;
					if (i < Math.floor(this.drawColorList.length / 2) && needColorCount > hasColorCount)
					{
						var bianStart = j;
						for (var k=0; k<=step; k++)
						{
							j++;
							this.userColorList.push(c);
							needColorCount =  showType.min - 1 - j;
							if (needColorCount <= hasColorCount)
							{
								break;
							}
						}
						if (j > bianStart + 1)
						{
							$('#divBianLimit .c').append('<span style="background-color:rgb(' + c.join(',') + ');">' + (bianStart+1) + '~' + (j) + ' 遍</span>');
						}
						else
						{
							$('#divBianLimit .c').append('<span style="background-color:rgb(' + c.join(',') + ');">' + (bianStart+1) + ' 遍</span>');
						}
					}
					else
					{
						j++;
						this.userColorList.push(c);
						$('#divBianLimit .c').append('<span style="background-color:rgb(' + c.join(',') + ');">' + (j+1) + ' 遍</span>');
					}
				}
				
				// 最后合格颜色
				var c =  this.drawColorList[this.drawColorList.length - 1];
				this.userColorList.push(c);
				$('#divBianLimit .c').append('<span style="background-color:rgb(' + c.join(',') + ');">合格</span>');
			}
			
			$('#divBianLimit').show();
		}
		else if (showType.limit)
		{
			var limit = showType.limit.split(',');
			$('#divLimit .c span').eq(0).html(limit[0]);
			$('#divLimit .c span').eq(1).html(limit[1]);
			$('#divLimit .c span').eq(2).html(limit[2]);
			$('#divLimit .min').html(showType.min + limit[3]);
			$('#divLimit .max').html(showType.max + limit[3]);

			$('#divLimit').show();
		}
	}

	this.resize = function()
	{
        DS.isAdd = false;
		var w = $(document).width();
		var h = $(document).height();
		this.canvasMain.width  = w;
		this.canvasMain.height = h;
		this.canvasCalc.width  = w;
		this.canvasCalc.height = h;
		this.canvasCar.width  = w;
		this.canvasCar.height = h;
		this.canvastext.width  = w;
		this.canvastext.height = h;
		this.w = w;
		this.h = h;

		this.ctxMain = this.canvasMain.getContext('2d');
		this.ctxCalc = this.canvasCalc.getContext('2d');
		this.ctxCar = this.canvasCar.getContext('2d');
		this.catext = this.canvastext.getContext('2d');

		this.ctxMain.globalCompositeOperation = "source-over";
		this.ctxMain.globalAlpha = 1.0;
		// this.catext.globalCompositeOperation = "source-over";
		// this.catext.globalAlpha = 1.0;
		// 将绘图坐标和GPS坐标系同一，反转画布
		/*
		this.ctxMain.translate(this.w, 0);
		this.ctxMain.scale(-1, 1);
		this.ctxCalc.translate(this.w, 0);
		this.ctxCalc.scale(-1, 1);
		*/
		this.ctxMain.translate(0, this.h);
		this.ctxMain.scale(1, -1);
		this.ctxCalc.translate(0, this.h);
		this.ctxCalc.scale(1, -1);
		this.catext.translate(0, this.h);
		this.catext.scale(1, -1);
        
        this.ctxMain.lineJoin = "round";
        //this.ctxMain.lineCap  = "round";

		this.layerColorInit();
		this.reset();
	}

	this.layerColorInit = function()
	{
		this.ctxCalc.fillStyle = '#fff';
		this.ctxCalc.fillRect(0, 0, this.w, this.h);
		this.ctxCalc.fillStyle = 'rgba(255,0,0,0.03)';
		for (var i=100; i>0; i--)
		{
			this.drawCalcRect([[i, this.h - 1], [i, this.h]], [[100, this.h - 1], [100, this.h]]);
		}

	    var imageData = this.ctxCalc.getImageData(0, 0, 100, 1);

	    this.layerColorList = [];
	    var lastColor = 0;
	    for (var i=0; i < imageData.data.length; i += 4)
	    {
	    	var color = imageData.data[i + 1];
	    	this.layerColorList[color] = i / 4;
	    }

	    var lastLayer = 99;
	    for (var i=0; i<256; i++)
	    {
	    	if (this.layerColorList[i])
	    	{
	    		lastLayer = this.layerColorList[i];
	    	}
	    	else
	    	{
	    		this.layerColorList[i] = lastLayer;
	    	}
	    }

	    //console.log(this.layerColorList);

		this.ctxCalc.fillStyle = '#fff';
		this.ctxCalc.fillRect(0, 0, this.w, this.h);
	}
	
	this.reset = function()
	{
		if (this.isBusy) return;

		var mapSize = this.pixel2map(this.w, this.h);

		this.rect = [this.center[0] - mapSize[0] / 2, this.center[1] - mapSize[1] / 2, this.center[0] + mapSize[0] / 2, this.center[1] + mapSize[1] / 2];

		this.showInfo('加载中...');
		this.showGps('GPS:' + this.center.join(','));

		DS.getData();
	}

	this.drawPaver = function()
	{
		var colorArea = '#f60';
		for (var i in DS.serverData.car_list)
		{
			var car = DS.serverData.car_list[i];
			if (car.car.unit_type != PAVER)
			{
				continue;
			}

			var perPoint = null;
			for (var j in car.gps_list)
			{
				var gps = car.gps_list[j].split(',');
				var pointLine = this.getPointLine(car.car, gps);
				if (perPoint == null || gps[5] == 0)
				{
					perPoint = pointLine;
					continue;
				}

				this.drawCarRect(perPoint, pointLine, colorArea);

				perPoint = pointLine;
			}
		}
	}

	this.reDraw = function()
	{
        // console.log('reDraw');
		var w = $(document).width();
		var h = $(document).height();
		this.canvasMainJq.css({left:0, top:0, width:w, height:h});
        if (!DS.isAdd)
        {
            this.clearAll();

            // 画工作区域
            if (this.workareaList)
            {
                for (var i=0; i<this.workareaList.length; i++)
                {
                    var workarea = this.workareaList[i];
					this.drawWorkArea(workarea.area, i);
                }
            }

            // 绘制背景
            var myDate = new Date();
  			var h = myDate.getHours();       //获取当前小时数(0-23)
  			if (h>17||h<7) {
            	this.ctxMain.fillStyle = "#696969";
           		this.ctxMain.fillRect(0,0,1000,1000);
			}
            // 绘制经纬度网格
            var gridHeight = (this.rect[3] - this.rect[1]);
            var gridUnit = 0.1;
            while(true)
            {
                if (gridUnit < gridHeight)
                {
                    break;
                }
                gridUnit = gridUnit / 10;
            }
            var gridLines = [];
            var gridUnitPer = 1 / gridUnit;
            var gridLonBegin = Math.floor(this.rect[0] * gridUnitPer) / gridUnitPer;
            for (var lon = gridLonBegin; lon < this.rect[2]; lon += gridUnit)
            {
                var xy = this.map2pixel(lon, this.center[1]);
                
                gridLines.push([xy[0], 0, xy[0], this.h]);
            }

            var gridLatBegin = Math.floor(this.rect[1] * gridUnitPer) / gridUnitPer;
            for (var lat = gridLatBegin; lat < this.rect[3]; lat += gridUnit)
            {
                var xy = this.map2pixel(this.center[0], lat);

                gridLines.push([0, xy[1], this.w, xy[1]]);
            }
            
            this.lines(gridLines, 'rgba(190,190,190,0.2)');

            // 绘制比例尺
            var ruleMeter = Math.round(this.w / 10 / this.zoom);
            ruleMeter = Math.floor(ruleMeter / 5) * 5;
            if (ruleMeter <= 0) ruleMeter = 1;
            var rulePixel = ruleMeter * this.zoom;
            $('#divRule p').html(ruleMeter + ' 米');
            $('#divRule div').css('width', rulePixel + 'px');
            
            this.drawPoint();
        }
        
		// console.log(DS.cirleList);
		// 绘制GPS点
		if (DS.cirleList != null && DS.cirleList.length > 0)
		{
			var showType = this.showTypeList[this.showTypeIndex];
			console.log('GPS数据量：', DS.cirleList.length);

			this.ctxCalc.fillStyle = 'rgba(255,0,0,0.03)';
			// 清楚文本画布
			this.catext.clearRect(0,0,1000,1000);
			// 画出真实点
			for (var i in DS.cirleList)
			{
				var item = DS.cirleList[i];
						
            	//var r = item.radius * this.zoom * 0.8;
            	var r = item.radius * this.zoom;
            	var mun = item.hit_count;
            	var xy = this.map2pixel(item.lon, item.lat);
				this.ctxCalc.beginPath();
				// 夯击次数绘制
				var mun = item.hit_count;
				var jw  = Math.floor(this.getMapRatio(item.lat)*this.zoom);
                this.drawText(mun, xy[0], xy[1]-jw*0.5,item.radius);
                // 绘制真实夯击点
				this.ctxCalc.arc(xy[0], xy[1], r, 0, 2 * Math.PI);
                this.ctxCalc.fill();
				
			}
			this.drawLayer(showType);
		}

		for (var i in this.drawCircleList)
		{
			var drawCircle = this.drawCircleList[i];
			
            //var r = (drawCircle.hammer_radius) * this.zoom * 0.8;
			var r = (drawCircle.radius) * this.zoom;
            var xy = this.map2pixel(drawCircle.lon, drawCircle.lat);
            var type = drawCircle.type;
            var myDate = new Date();
  			var h = myDate.getHours();       //获取当前小时数(0-23)
            if (type!=1) {
            	if (h>17||h<7) {
            		this.circleLine(xy[0], xy[1], r, '#CFCFCF', 1);
            	}else{
            		this.circleLine(xy[0], xy[1], r, '#AAAAAA', 1);
            	}
            	
            }else{
            	this.circle(xy[0], xy[1], r, "red");
            } 
		}

		// 绘制中心点
		var w2 = Math.round(this.w / 2) + 0.5;
		var h2 = Math.round(this.h / 2) + 0.5;
		this.line(w2-6, h2, w2+6, h2, 'rgba(0,0,0,0.5)');
		this.line(w2, h2-6, w2, h2+6, 'rgba(0,0,0,0.5)');
		this.circle(w2, h2, 2, 'rgba(0,0,0,0.5)');
	}

	this.getRangeColor = function(colorList, showType, val)
	{
		var color = colorList[3];
		if (val < showType.min)
		{
			color = colorList[2];
		}
		else if (val > showType.max)
		{
			color = colorList[0];
		}
		else
		{
			color = colorList[1];
		}

		return color;
	}

	this.drawLayer = function(showType)
	{
	    var imageMainData = this.ctxMain.getImageData(0, 0, this.w, this.h);
	    var imageCalcData = this.ctxCalc.getImageData(0, 0, this.w, this.h);

		var lastGreen = 0;
	    for (var i=0; i < imageCalcData.data.length; i += 4)
	    {
	    	if (imageCalcData.data[i + 1] == 255)
	    	{
	    		continue;
	    	}
	    	var o_green = imageCalcData.data[i + 1];

	    	var n = this.layerColorList[o_green];

			var alpha = 255;
			var red = 0;
			var green = 0;
			var blue = 0;

			if (this.userColorList.length > 0)
			{
				var userColor = n >= showType.min ? this.userColorList[this.userColorList.length - 1] : this.userColorList[(n - 1)];
				red   = userColor[0];
				green = userColor[1];
				blue  = userColor[2];
			}
			else
			{
				if(n < showType.min)
				{
					blue = 255;
					red = 200 - Math.round(200 * n / showType.min);
					red = Math.max(red, 0);
					green = red;
				}
				else if(n < showType.max)
				{
					green = 255;
					
					red = 200 - Math.round(200 * (n - showType.min) / (showType.max - showType.min));
					red = Math.max(red, 0);
					blue = red;
				}
				else
				{
					red = 255;
					blue = 200 - Math.round(200 * (n - showType.max) / 5);
					blue = Math.min(blue, 200);
					blue = Math.max(blue, 0);
					green = blue;
				}
			}
	        imageMainData.data[i] = red;
	        imageMainData.data[i + 1] = green;
	        imageMainData.data[i + 2] = blue;
	        imageMainData.data[i + 3] = alpha;
	    }

	    this.ctxMain.putImageData(imageMainData, 0, 0);

        /*
		this.ctxCalc.fillStyle = '#fff';
		this.ctxCalc.fillRect(0, 0, this.w, this.h);
        */
	}

	this.checkRect = function(p1, p2)
	{
		if (
			(p1[0][0] < 0 || p1[0][0] > this.w) &&
			(p1[0][1] < 0 || p1[0][1] > this.h) &&
			(p1[1][0] < 0 || p1[1][0] > this.w) &&
			(p1[1][1] < 0 || p1[1][1] > this.h)
		)
		{
			if (
				(p2[0][0] < 0 || p2[0][0] > this.w) &&
				(p2[0][1] < 0 || p2[0][1] > this.h) &&
				(p2[1][0] < 0 || p2[1][0] > this.w) &&
				(p2[1][1] < 0 || p2[1][1] > this.h)
			)
			{
				return false;
			}
		}

		return true;
	}

	this.checkLine = function(x1, y1, x2, y2)
	{
		if (
			(x1 < 0 || x1 > this.w) &&
			(y1 < 0 || y1 > this.h) &&
			(x2 < 0 || x2 > this.w) &&
			(y2 < 0 || y2 > this.h)
		)
		{
			return false;
		}

		return true;
	}

	this.drawCalcRect = function(p1, p2)
	{
		if (!this.checkRect(p1, p2)) return;
		
		var line1 = {
			Start:{X:p1[0][0], Y:p1[0][1]},
			End  :{X:p2[0][0], Y:p2[0][1]},
		};
		
		var line2 = {
			Start:{X:p1[1][0], Y:p1[1][1]},
			End  :{X:p2[1][0], Y:p2[1][1]},
		};
		
		// 判断是否纽了
		/*
		if (this.checkTwoLineCrose(line1, line2))
		{
			console.log('相交纽了', line1, line2);
			// 纽了后交换线段方向
			var p3 = [];
			p3[0] = p2[1];
			p3[1] = p2[0];
			p2 = p3;
		}
		*/

		this.ctxCalc.beginPath();
		this.ctxCalc.moveTo(p1[0][0], p1[0][1]);
		this.ctxCalc.lineTo(p1[1][0], p1[1][1]);
		this.ctxCalc.lineTo(p2[1][0], p2[1][1]);
		this.ctxCalc.lineTo(p2[0][0], p2[0][1]);
		this.ctxCalc.closePath();
		this.ctxCalc.fill();
	}
	
	this.drawWorkArea = function(gps_list, index)
	{
		this.ctxMain.beginPath();
		for (var i=0; i<gps_list.length; i++)
		{
			var gps = gps_list[i];
			var p = this.map2pixel(gps.lon, gps.lat);
			if (i == 0)
			{
				this.ctxMain.moveTo(p[0], p[1]);
			}
			else
			{
				this.ctxMain.lineTo(p[0], p[1]);
			}
		}
		this.ctxMain.closePath();
		//var img = imgAreaBackground[index%imgAreaBackground.length];
		//this.ctxMain.fillStyle = this.ctxMain.createPattern(img, "repeat");
		var colorList = ['#eff', '#fef', '#ffe', '#fee', '#efe', '#eef'];
		this.ctxMain.fillStyle = colorList[index%colorList.length];
		this.ctxMain.strokeStyle = 'gray';
		this.ctxMain.lineWidth = 1;
		this.ctxMain.fill();
		this.ctxMain.stroke();
	}

	this.drawCarRect = function(p1, p2, color)
	{
		if (!this.checkRect(p1, p2)) return;

		if (this.ctxMain.fillStyle != color)
		{
			this.ctxMain.fillStyle = color;
		}
		
		var line1 = {
			Start:{X:p1[0][0], Y:p1[0][1]},
			End  :{X:p2[0][0], Y:p2[0][1]},
		};
		
		var line2 = {
			Start:{X:p1[1][0], Y:p1[1][1]},
			End  :{X:p2[1][0], Y:p2[1][1]},
		};
		
		// 判断是否纽了
		if (this.checkTwoLineCrose(line1, line2))
		{
			console.log('相交纽了', line1, line2);
			// 纽了后交换线段方向
			var p3 = [];
			p3[0] = p2[1];
			p3[1] = p2[0];
			p2 = p3;
		}

		this.ctxMain.beginPath();
		this.ctxMain.moveTo(p1[0][0], p1[0][1]);
		this.ctxMain.lineTo(p1[1][0], p1[1][1]);
		this.ctxMain.lineTo(p2[1][0], p2[1][1]);
		this.ctxMain.lineTo(p2[0][0], p2[0][1]);
		this.ctxMain.closePath();
		this.ctxMain.fill();

		this.line(p1[0][0], p1[0][1], p2[0][0], p2[0][1], 'rgba(0,0,0,0.3)');
		this.line(p1[1][0], p1[1][1], p2[1][0], p2[1][1], 'rgba(0,0,0,0.3)');
	}

	this.getPointLine = function(car, gps)
	{
		var top = parseInt(car.offset_y) * this.zoom / 100.0;
		var left = parseInt(car.offset_x) * this.zoom / 100.0;
		var width = parseInt(car.width) * this.zoom / 100.0;
		//console.log(top, left, width);
		//console.log(gps);

		var xy = this.map2pixel(gps[0], gps[1]);
		var angle = gps[2] * -1;

		//var xy3 = this.goAngle(xy[0], xy[1], angle, 50);
		//this.line(xy[0], xy[1], xy3[0], xy3[1], 'red');

		//this.circle(xy[0], xy[1], 2, 'red');

		var xy_front = this.goAngle(xy[0], xy[1], angle, top);
		var xy1 = this.goAngle(xy_front[0], xy_front[1], angle - 90, left);
		var xy2 = this.goAngle(xy_front[0], xy_front[1], angle + 90, width - left);

		/*
		xy = this.goAngle(xy[0], xy[1], angle, top);
		xy = this.goAngle(xy[0], xy[1], angle + 270, left);
		var xy1 = this.goAngle(xy[0], xy[1], angle - 90, width / 2);
		var xy2 = this.goAngle(xy[0], xy[1], angle + 90, width / 2);
		*/

		//this.circle(xy[0], xy[1], 2, 'blue');

		//this.line(xy1[0], xy1[1], xy2[0], xy2[1]);

		return [xy1, xy2];
	}

	this.getPoint = function(car, gps)
	{
		var top = parseInt(car.offset_y) * this.zoom / 100.0;
		var left = parseInt(car.offset_x) * this.zoom / 100.0;
		var width = parseInt(car.width) * this.zoom / 100.0;
		//console.log(top, left, width);
		//console.log(gps);

		var xy = this.map2pixel(gps[0], gps[1]);
		var angle = gps[2];

		//var xy3 = this.goAngle(xy[0], xy[1], angle, 50);
		//this.line(xy[0], xy[1], xy3[0], xy3[1], 'red');

		//this.circle(xy[0], xy[1], 2, 'red');

		var xy_front = this.goAngle(xy[0], xy[1], angle, top);
		var xy = this.goAngle(xy_front[0], xy_front[1], angle + 90, width / 2 - left);

		return xy;
	}

	this.clearAll = function()
	{
		this.ctxMain.fillStyle = '#fff';
		this.ctxMain.fillRect(0, 0, this.w, this.h);
		this.ctxCalc.fillStyle = '#fff';
		this.ctxCalc.fillRect(0, 0, this.w, this.h);
	}

	this.debug = function()
	{
		console.log(this);
	}

	// 获取当前维度的经纬度比例
	this.getMapRatio = function(lat)
	{
		// 在lat维度上1经度的长度。
		// （6370*cosA*2*Л）/360
		//var lon = 6370 * 2 * Math.PI * Math.cos(lat * Math.PI / 180) / 360;
		//return lon;

		return Math.cos(lat * Math.PI / 180);
	}

	// 获取两点间的角度
	this.getAngle = function(x1, y1, x2, y2)
	{
		if (x2 == x1)
		{
			//return y1 > y2 ? 0 : 180;
		}

		var tan = Math.atan2(x2 - x1, y2 - y1) * 180 / Math.PI;
		return 180 - tan;
	}

	// 获取两点之间的距离
	this.getDis = function(x1, y1, x2, y2)
	{
		var dis = Math.sqrt(Math.pow(x1 - x2, 2) + Math.pow(y1 - y2, 2));

		return dis;
	}

	// 获取从指定点向指定角度偏移后的位置
	this.goAngle = function(x, y, r, m)
	{
		var deg = r * Math.PI / 180.0;
		var x1 = x + Math.sin(deg) * m;
		var y1 = y + Math.cos(deg) * m * -1;

		return [x1, y1];
	}
	
	this.getCarImage = function(pointLine, deg)
	{
		var img = imgRoll;
		var canvasCar = document.getElementById("canvasCar");
		var cxt = canvasCar.getContext("2d");
		
		var img_width  = this.getDis(pointLine[0][0], pointLine[0][1], pointLine[1][0], pointLine[1][1]);
		var img_height = img_width / img.width * img.height;
	
		var rad = deg * Math.PI / 180;
		
		var w = Math.abs(img_height * Math.sin(rad)) + Math.abs(img_width * Math.cos(rad));
		var h = Math.abs(img_height * Math.cos(rad)) + Math.abs(img_width * Math.sin(rad));
		canvasCar.width  = w;
		canvasCar.height = h;
		cxt.translate(w / 2, h / 2);
	
		cxt.rotate(rad);
		cxt.drawImage(img,-0.5*img_width,-0.5*img_height, img_width, img_height);
		
		var imgNew = new Image();
		imgNew.src = canvasCar.toDataURL("image/png");
		return imgNew;
	}
    
    this.drawPoint = function()
    {
        for (var i=0; i<this.drawPointList.length; i++)
        {
            var point = this.drawPointList[i];
            var color = point.color || 'red';
            var r = (point.r || 10) * this.zoom;
            var xy = this.map2pixel(point.lon, point.lat);
            this.circle(xy[0], xy[1], r, color);
        }
    }
    
    this.addPointList = function(list)
    {
        for (var i=0; i<list.length; i++)
        {
            this.drawPointList.push(list[i]);
        }
    }

	this.circle = function(x, y, r, color)
	{
		this.ctxMain.beginPath();
		this.ctxMain.arc(x, y, r, 0, 2 * Math.PI);
		this.ctxMain.fillStyle = color || 'red';
		this.ctxMain.fill();
	}

	this.circleLine = function(x, y, r, color, width)
	{
		if (color) this.ctxMain.strokeStyle = color;
		if (width) this.ctxMain.lineWidth = width;

		this.ctxMain.beginPath();
		this.ctxMain.arc(x, y, r, 0, 2 * Math.PI);
		this.ctxMain.stroke();
	}

	this.line = function(x1, y1, x2, y2, color, width)
	{
		if (!this.checkLine(x1, y1, x2, y2)) return;

		if (color) this.ctxMain.strokeStyle = color;
		if (width) this.ctxMain.lineWidth = width;

		this.ctxMain.beginPath();
		this.ctxMain.moveTo(x1, y1);
		this.ctxMain.lineTo(x2, y2);
		this.ctxMain.stroke();
	}

	this.lines = function(arr, color, width)
	{
		this.ctxMain.strokeStyle = color || 'black';
		this.ctxMain.lineWidth = width || 1;
		this.ctxMain.beginPath();
        
        var lastPoint = [-10000, -10000];
		for (var i in arr)
		{
			if (!this.checkLine(arr[i][0], arr[i][1], arr[i][2], arr[i][3])) continue;

            if (lastPoint[0] != arr[i][0] || lastPoint[1] != arr[i][1])
            {
                this.ctxMain.moveTo(arr[i][0], arr[i][1]);
            }
			this.ctxMain.lineTo(arr[i][2], arr[i][3]);
            lastPoint = [arr[i][2], arr[i][3]];
		}
		this.ctxMain.stroke();
	}

	this.pixel2map = function(w, h)
	{
		var wMeter = w / this.zoom;
		var hMeter = h / this.zoom;

		var lonWidth  = wMeter / this.mapMeter / this.mapRatio;
		var latHeight = hMeter / this.mapMeter;

		return [lonWidth, latHeight];
	}

	this.map2pixel = function(lon, lat)
	{
		lon = lon - this.center[0];
		lat = lat - this.center[1];

		var wMeter = lon * this.mapMeter * this.mapRatio;
		var hMeter = lat * this.mapMeter;

		var x = wMeter * this.zoom + this.w / 2;
		var y = hMeter * this.zoom + this.h / 2;

		return [x, y];
	}

	this.getLonLat = function(x, y)
	{
		x = x - this.w / 2;
		y = y - this.h / 2;

		var mapSize = this.pixel2map(x, y);

		var lon = this.center[0] + mapSize[0];
		var lat = this.center[1] - mapSize[1];

		return [lon, lat];
	}

	this.getRandColor = function()
	{
		var colorList = ['#f00', '#0f0', '#00f', '#ff0', '#f0f', '#0ff'];

		return colorList[Math.floor(Math.random() * colorList.length)];
	}
	
	this.showInfo = function(txt, color)
	{
		color = color || 'black';
		$('#divInfo').css('color', color);
		$('#divInfo').html(txt);
	}

	this.showGps = function(txt, color)
	{
		color = color || 'black';
		$('#divGps').css('color', color);
		$('#divGps').html(txt);
	}

	this.zoomBo = function(bo)
	{	
		var zoomNew = this.zoom + (bo ? 1 : -1) * (this.zoom / 5);
		this.zoomFn(zoomNew);
		this.zoomcar(zoomNew);
	}

	this.zoomcar = function(zoomNew)
	{
		var car_h = 12*zoomNew;
		var w = $(document).width();
		var w1= $('.left-top',window.parent.document).width();
		var h = $(document).height();
		var w2 = Math.round(this.w / 2) + w1 - car_h/2;
		var h2 = Math.round(this.h / 2) + 0.5 - car_h/2;
		$('#car_hammer_div', window.parent.document).css({"position":"absolute","left":w2,"top":h2});
		$('#car_hammer', window.parent.document).css({"height":car_h});
	}

	this.zoomFn = function(zoomNew)
	{
		if (!this.isInit) return;
		if (this.isBusy) return;
        zoomNew = Math.round(zoomNew * 100) / 100;

        if (zoomNew < this.zoomMin || zoomNew > this.zoomMax)
        {
        	return;
        }

        if (this.zoom != zoomNew)
        {
			if (this.isBusy) return;

			console.log('1米多少像素：', zoomNew);
        	this.zoom = zoomNew;
            DS.isAdd = false;
			this.reset();
        }
	}

	this.move = function(x, y)
	{
		return;
		if (!this.isInit) return;
		if (this.isBusy) return;
		this.canvasMainJq.css('left', x + 'px');
		this.canvasMainJq.css('top',  y + 'px');
        
        if (this.carDrawType == 'html5')
        {
            $('.car').stop();
            $('.car').each(function(){
                var initLeft = $(this).data('init_left');
                var initTop  = $(this).data('init_top');
                $(this).css('left', (x + initLeft) + 'px');
                $(this).css('top' , (y + initTop) + 'px');
            });
        }
	}

	this.moveUp = function()
	{
		return;
		if (!this.isInit) return;
		if (this.isBusy) return;
		var top  = parseInt(draw.canvasMainJq.css('top'));
		var left = parseInt(draw.canvasMainJq.css('left'));
		if (top == 0 && left == 0)
		{
			return;
		}
		
		var mapSize = this.pixel2map(left, top);
		var lon = this.center[0] - mapSize[0];
		var lat = this.center[1] + mapSize[1];

		lon = lon.toFixed(8);
		lat = lat.toFixed(8);
		lon = parseFloat(lon);
		lat = parseFloat(lat);

		this.center = [lon, lat];

        DS.isAdd = false;
		this.reset();
		this.isMove = false;
		
		this.isCenterRun = false;
		window.clearTimeout(this.isCenterRunTimeout);
		this.isCenterRunTimeout = window.setTimeout(function(){
			draw.isCenterRun = true;
		}, 10000);
	}

	this.bindEvent = function()
	{
		$(window).resize(function(){
			draw.resize();
		});

		$('#selShowType').change(function(){
			draw.showTypeIndex = $(this).val();
			draw.showTypeChange();
            DS.isAdd = false;
			draw.resize();
		});

		var mc = new Hammer.Manager(draw.canvasMainJq[0]);

		var pinch = new Hammer.Pinch();
		var pan = new Hammer.Pan();
		var tap = new Hammer.Tap();
		mc.add([pinch, pan, tap]);

		// tap 双击
		var tapDbl = false;
		mc.on("tap", function(ev) {
			if (tapDbl)
			{
				draw.zoomBo(true);
				tapDbl = false;
				return;
			}
			tapDbl = true;
			window.setTimeout(function(){
				tapDbl = false;
			}, 300);
		});

		// panstart panmove panend
		mc.on("panmove", function(ev) {
			if (this.isPinch) return;
			if (Math.abs(ev.deltaX) + Math.abs(ev.deltaY) < 5)
			{
				return;
			}
			
			draw.isMove = true;
			draw.move(ev.deltaX, ev.deltaY);
		});
		mc.on("panend", function(ev) {
			if (this.isPinch) return;
			draw.moveUp();
			draw.isMove = false;
		});

		// pinchstart pinchmove pinchend
		var zoomOld = 0;
		var zoomNew = 0;
		mc.on("pinchstart", function(ev) {
		    zoomOld = draw.zoom;
		    zoomNew = draw.zoom;
		    this.isPinch = true;
            $('.car').hide();
		});
		mc.on("pinchend", function(ev) {
		    draw.zoomFn(zoomNew);
		    window.setTimeout(function(){
		    	this.isPinch = false;
		    }, 100);
		});
		mc.on("pinchmove", function(ev) {
			var z = zoomOld * ev.scale;
	        if (z < draw.zoomMin || z > draw.zoomMax)
	        {
	        	return;
	        }
	        zoomNew = z;
			var w = parseInt(draw.w * ev.scale);
			var h = parseInt(draw.h * ev.scale);
			var top  = (draw.h - h) / 2;
			var left = (draw.w - w) / 2;
			draw.canvasMainJq.css({
				width: w + 'px',
				height: h + 'px',
				top: top + 'px',
				left: left + 'px',
			});
		});

		draw.canvasMainJq.bind('mousewheel wheel', function(event){
	        var e = event.originalEvent;
	        e.stopPropagation();
	        e.preventDefault();

	        var dy = (e.wheelDeltaY || - e.deltaY) / 1000;
			draw.zoomBo(dy > 0);
		});

		var mouseDownX = 0;
		var mouseDownY = 0;
		draw.canvasMainJq.bind('mousedown', function(event) {
			var e = event.originalEvent;
			e.stopPropagation();
			e.preventDefault();

			mouseDownX = e.clientX;
			mouseDownY = e.clientY;
			draw.isMove = true;
		});

		draw.canvasMainJq.bind('mouseup', function(event) {
			if (!draw.isInit) return;
			if (draw.isBusy) return;
			draw.isMove = false;
			draw.moveUp();
		});

		draw.canvasMainJq.bind('dblclick', function(event) {
			draw.zoomBo(true);
		});

		draw.canvasMainJq.bind('mousemove', function(event) {

			if (!draw.isInit) return;

			var e = event.originalEvent;
			e.stopPropagation();
			e.preventDefault();

			//var xy = draw.getLonLat(e.clientX, e.clientY);
			//console.log(xy);

			if (!draw.isMove) return;

			if (Math.abs(mouseDownX - e.clientX) + Math.abs(mouseDownY - e.clientY) < 5)
			{
				return;
			}

			var x = e.clientX - mouseDownX;
			var y = e.clientY - mouseDownY;
			
			draw.move(x, y);
		});

	}
    
    this.drawCarList = function()
    {
        if (!DS.serverData.car_last_gps)
        {
            return;
        }
        
        if (this.carDrawType == 'canvas')
        {
            for (var key in DS.serverData.car_last_gps)
            {
                var car_gps = DS.serverData.car_last_gps[key];
                var pointLine = this.getPointLine(
                    {width:car_gps.width, offset_x:car_gps.offset_x, offset_y:car_gps.offset_y}, 
                    [car_gps.lon, car_gps.lat, car_gps.drct]
                );
                
                var p1 = pointLine[0], p2 = pointLine[1];
                
                if (p1[0] < 0 && p2[0] < 0) continue;
                if (p1[1] < 0 && p2[1] < 0) continue;
                if (p1[0] > this.w && p2[0] > this.w) continue;
                if (p1[1] > this.h && p2[1] > this.h) continue;
                
                this.line(p1[0], p1[1], p2[0], p2[1], 'red');
                var img = this.getCarImage(pointLine, car_gps.drct * -1);
                var x = p1[0] + (p2[0] - p1[0]) / 2 + img.width*-0.5;
                var y = p1[1] + (p2[1] - p1[1]) / 2 + img.height*-0.5;
                this.ctxMain.drawImage(img, x, y);
            }
        }
        else if (this.carDrawType == 'html5')
        {
            if (!DS.isAdd)
            {
                $('.car').remove();
            }
            else
            {
                $('.car').addClass('wait_del');
            }
            
            for (var key in DS.serverData.car_last_gps)
            {
                var car_gps = DS.serverData.car_last_gps[key];
                var pointLine = this.getPointLine(
                    {width:car_gps.width, offset_x:car_gps.offset_x, offset_y:car_gps.offset_y}, 
                    [car_gps.lon, car_gps.lat, car_gps.drct]
                );
                
                var p1 = pointLine[0], p2 = pointLine[1];
                
                if (p1[0] < 0 && p2[0] < 0) continue;
                if (p1[1] < 0 && p2[1] < 0) continue;
                if (p1[0] > this.w && p2[0] > this.w) continue;
                if (p1[1] > this.h && p2[1] > this.h) continue;
                
                var carCenter = [p1[0] + (p2[0] - p1[0]) / 2, p1[1] + (p2[1] - p1[1]) / 2];
                
                var w = $('#imgCarRoller').width();
                var h = $('#imgCarRoller').height();
                var w1 = this.getDis(p1[0], p1[1], p2[0], p2[1]);
                var h1 = h * w1 / w;
                
                var left = carCenter[0] - w1 / 2;
                var top  = this.h - carCenter[1] - h1 / 2;
                
                var imgNew = $('#car_' + car_gps.sid);
                if (imgNew.size() == 0)
                {
                    imgNew = $('#imgCarRoller').clone(true);
                    imgNew.attr('id', 'car_' + car_gps.sid);
                    
                    imgNew.css('left', left + 'px');
                    imgNew.css('top', top + 'px');
                }
                else
                {
                    imgNew.removeClass('wait_del');
                    imgNew.stop(); 
                    imgNew.animate({
                        left:left + 'px',
                        top:top + 'px',
                    }, 1000);
                }
                
                imgNew.css('width', w1 + 'px');
                imgNew.css('height', h1 + 'px');
                imgNew.css('transform', 'rotate(' + car_gps.drct + 'deg)');
                imgNew.data('init_left', left);
                imgNew.data('init_top', top);
                
                imgNew.addClass('car');
                $('body').append(imgNew);
                imgNew.show();
            }
            
            $('.wait_del').remove();
        }
    }

    this.resetCarList = function()
    {
        if (this.carDrawType == 'canvas')
        {
            return;
        }
    }
	
	// 判断两条线段是否相交。
	this.checkTwoLineCrose = function(line1, line2)
	{
		return this.checkCrose(line1, line2) && this.checkCrose(line2, line1);
	}
	
	// 计算两个向量的叉乘。
	this.crossMul = function(pt1, pt2)
	{
		return pt1.X * pt2.Y - pt1.Y * pt2.X;
	}

	// 判断直线2的两点是否在直线1的两边。
	this.checkCrose = function(line1, line2)
	{
		var v1 = {};
		var v2 = {};
		var v3 = {};

		v1.X = line2.Start.X - line1.End.X;
		v1.Y = line2.Start.Y - line1.End.Y;

		v2.X = line2.End.X - line1.End.X;
		v2.Y = line2.End.Y - line1.End.Y;

		v3.X = line1.Start.X - line1.End.X;
		v3.Y = line1.Start.Y - line1.End.Y;

		return (this.crossMul(v1, v3) * this.crossMul(v2, v3) <= 0);
	}

	// 文本
	this.drawText =function(text,x,y,size) { 
		this.catext.beginPath();
		this.catext.fillStyle = '#000';
		this.catext.strokeStyle = '#000';
		//设置字体样式
		var f_size = this.zoom*size*1.5;
		this.catext.font = f_size+"px FangSong";
		this.catext.textAlign="center";
        this.catext.save();//保存状态
		this.catext.translate(x,y);//设置画布上的(0,0)位置，也就是旋转的中心点
		this.catext.scale(-1, 1);
		this.catext.rotate(90*Math.PI/6);
		this.catext.fillText(text, 0,0);
		this.catext.strokeText(text, 0,0);
		this.catext.restore();//恢复状态
   };

   // 绘制强夯机
   this.canvas_Car = function(gnss){
       console.log('canvas_Car');
   		// 中心 
   		this.ctxCar.clearRect(0,0,1000,1000);

  		var lon = parseFloat(gnss.plon);
        var lat = parseFloat(gnss.plat);
        var xy  = this.map2pixel(lon, lat);
        var jw = draw.getMapRatio(lat);
        //天线1GPS
        var gps_lon = parseFloat(gnss.lon);
        var gps_lat = parseFloat(gnss.lat);
        var gpsxy   = this.map2pixel(gps_lon, gps_lat);

		 // 车底盘
        var gps = this.goAngle(xy[0], xy[1], 0, Math.floor(3.5*this.zoom));
        var gps1 = this.goAngle(gps[0], gps[1], 270, Math.floor(2.5*this.zoom));

		// 车主体
        var gp2 = this.goAngle(xy[0], xy[1], 0, Math.floor(2.5*this.zoom));
        var gps3 = this.goAngle(gp2[0], gp2[1], 270, Math.floor(1.5*this.zoom));

		// 锤架
		var gp4 = this.goAngle(xy[0], xy[1], 0, Math.floor(3*this.zoom));
        var gps5 = this.goAngle(gp4[0], gp4[1], 90, Math.floor(1.5*this.zoom));
        var gps6 = this.goAngle(gp4[0], gp4[1], 270, Math.floor(1.5*this.zoom));
        var r = this.getDis(xy[0],xy[1],gpsxy[0],gpsxy[1]);
        var r_max = this.zoom*20;
       if (r>r_max) {
       		this.showInfo("暂无与数据！", 'red');
       		console.log('车辆GPS获取有误！');
       		this.isBusy=false;
       		var gps7 = this.goAngle(xy[0], xy[1], 0, r_max);
       }else{
       		var gps7 = this.goAngle(xy[0], xy[1], 0, r);
       }
        

        // 中心与天线1的夹角
        var workAngle = gnss.workAngle;
        if (workAngle==undefined) {
        	var a = draw.getAngle(xy[0],xy[1],gpsxy[0],gpsxy[1]);
        	// var oPoints = new GpsPoint(lon, lat);     // 当前夯锤坐标
	        // var fPoints = new GpsPoint(gps_lon,gps_lat);     // 当前夯锤坐标
	        // var workAngle =  oPoints.getNorthAngle(fPoints);    // 当前夯锤到预期点的方向
        }
        // 旋转到正确的车辆方法
  		this.ctxCar.save();   
        this.ctxCar.translate(xy[0], xy[1]);
        // workAngle = 87;
  		this.ctxCar.rotate(workAngle*Math.PI/180);//旋转47度  
  		console.log("车辆角度："+workAngle); 
        this.ctxCar.translate(-xy[0], -xy[1]);

        this.ctxCar.fillStyle="#0066FF";
		this.ctxCar.fillRect(gps1[0],gps1[1],Math.floor(2.5*this.zoom)*2,Math.floor(3.5*this.zoom)*2);

		this.ctxCar.fillStyle="#990099";
		this.ctxCar.fillRect(gps3[0],gps3[1],Math.floor(1.5*this.zoom)*2,Math.floor(2.5*this.zoom)*2);

        this.ctxCar.beginPath();

	    //设置线条颜色为蓝色
	    this.ctxCar.strokeStyle = "yellow";
	    // 设置线条的宽度
        this.ctxCar.lineWidth = 4;
	    //设置路径起点坐标
	    this.ctxCar.moveTo(gps5[0],gps5[1]);
	    //绘制直线线段到坐标点(60, 50)
	    this.ctxCar.lineTo(gps6[0],gps6[1]);
	    //绘制直线线段到坐标点(60, 90)
	    this.ctxCar.lineTo(gps7[0],gps7[1]);    
	    //先关闭绘制路径。注意，此时将会使用直线连接当前端点和起始端点。
	    this.ctxCar.closePath();
	    //最后，按照绘制路径画出直线
	    this.ctxCar.stroke();
	    if (r>25) {
	    	this.circleLine_car(xy[0], xy[1], r, '#AAAAAA', 2);//覆盖范围
 		}

 		var radius = parseFloat(gnss.radius);
  		this.circle_car(gps7[0],gps7[1], Math.floor(radius*0.6*this.zoom), "orange"); //天线1
  		this.circle_car(gps7[0],gps7[1], Math.floor(radius*0.3*this.zoom), "#000"); //天线1

  		this.ctxCar.restore();//恢复状态

  		// 绘制十字线
	    // for (var i = 1; i < 73; i++) {
	    // 	r = i*5;
	    // 	this.circke(xy[0], xy[1],r);
	    // }
   };
    this.circke = function(x,y,r) {
    	if (r!=85) {
    		var gps = this.goAngle(x, y, r, 200);
    	}else{
    		var gps = this.goAngle(x, y, r, 400);
    	}
	    this.ctxCar.moveTo(x, y);
	    this.ctxCar.lineTo(gps[0], gps[1]);
	    this.ctxCar.lineWidth = 1.0; // 设置线宽
	    this.ctxCar.strokeStyle = '#666'; // 设置线的颜色
	    this.ctxCar.stroke();  
   }

   // 车的画圆
    this.circle_car = function(x, y, r, color)
	{	
		this.ctxCar.beginPath();
		this.ctxCar.arc(x, y, r, 0, 2 * Math.PI);
		this.ctxCar.fillStyle = color || 'red';
		this.ctxCar.fill();
	}

	 // 车的画圆
	this.circleLine_car = function(x, y, r, color, width)
	{
		
		if (color) this.ctxCar.strokeStyle = color;
		if (width) this.ctxCar.lineWidth = width;

		this.ctxCar.beginPath();
		this.ctxCar.arc(x, y, r, 0, 2 * Math.PI);
		this.ctxCar.stroke();
	}


	// 连个数字相加
	function add(a,b){
	  suma=a+b;
	  return suma;
	}
	// 两个数字相减
	function sum(a,b){
	  sumb=a-b;
	  return sumb;
	}
	// 连个数字相乘
	function app(a,b){
	  sumc=a*b;
	  return sumc;
	}
	// 连个数字相除
	function divs(a,b){
	  sumd=a/b;
	  return sumd;
	}

	
};
