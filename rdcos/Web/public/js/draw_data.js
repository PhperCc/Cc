var Car = (function () {
    function Car() {
        this.no = '';
        this.lon = 0;
        this.lat = 0;
        this.deg = 0;
        this.carWidth = 2.3;
        this.carHeight = 5;
        this.img = '/img/car_roller.png';
        this.imgWidth = 100;
        this.imgHeight = 237;
        this.updateTime = '';
        this.info = '';
        this.name = '';
        this.isOnline = false;
        this.isPaver = false;
        this.show = true;
        this.color = '';
    }
    return Car;
}());
var CarGps = (function () {
    function CarGps() {
        this.id = '';
        this.car_no = '';
        this.lon = 0;
        this.lat = 0;
        this.deg = null;
        this.last_id = '';
        this.data = [];
    }
    return CarGps;
}());
var CarDrawType;
(function (CarDrawType) {
    CarDrawType[CarDrawType["line"] = 0] = "line";
    CarDrawType[CarDrawType["path"] = 1] = "path";
    CarDrawType[CarDrawType["data"] = 2] = "data";
    CarDrawType[CarDrawType["times"] = 3] = "times";
})(CarDrawType || (CarDrawType = {}));
;
var ColorRange = (function () {
    function ColorRange(min, max, color, colorMin) {
        if (colorMin === void 0) { colorMin = null; }
        this.min = min;
        this.max = max;
        this.color = color;
        this.colorMin = colorMin;
    }
    return ColorRange;
}());
var ShapeType;
(function (ShapeType) {
    ShapeType[ShapeType["line"] = 0] = "line";
    ShapeType[ShapeType["area"] = 1] = "area";
    ShapeType[ShapeType["circle"] = 2] = "circle";
    ShapeType[ShapeType["circleLine"] = 3] = "circleLine";
    ShapeType[ShapeType["lineList"] = 4] = "lineList";
    ShapeType[ShapeType["rect"] = 5] = "rect";
    ShapeType[ShapeType["rectLine"] = 6] = "rectLine";
    ShapeType[ShapeType["text"] = 7] = "text";
})(ShapeType || (ShapeType = {}));
var GpsDrawData = (function () {
    function GpsDrawData() {
    }
    GpsDrawData.init = function () {
        var _this = this;
        this.timesColorList = [
            new ColorRgb(204, 0, 0),
            new ColorRgb(0, 153, 0),
            new ColorRgb(204, 153, 0),
            new ColorRgb(51, 0, 153),
            new ColorRgb(0, 102, 153),
            new ColorRgb(102, 0, 153),
            new ColorRgb(204, 204, 0),
            new ColorRgb(204, 102, 0),
            new ColorRgb(0, 51, 153),
            new ColorRgb(153, 0, 102),
        ];
        this.dataColorRange = [
            new ColorRange(120, 140, new ColorRgb(0, 0, 255), new ColorRgb(155, 155, 255)),
            new ColorRange(140, 170, new ColorRgb(0, 155, 0), new ColorRgb(0, 255, 0)),
            new ColorRange(170, 170, new ColorRgb(255, 155, 155), new ColorRgb(255, 0, 0)),
        ];
        this.carColorList = [
            'rgba(204,   0,   0, 0.5)', 'rgba(  0, 153,   0, 0.5)', 'rgba(204, 153,   0, 0.5)', 'rgba( 51,   0, 153, 0.5)',
            'rgba(  0, 102, 153, 0.5)', 'rgba(102,   0, 153, 0.5)', 'rgba(204, 204,   0, 0.5)', 'rgba(204, 102,   0, 0.5)',
            'rgba(  0,  51, 153, 0.5)', 'rgba(153,   0, 102, 0.5)',
        ];
        window.setInterval(function () {
            if (_this.trackCarNo == '') {
                return;
            }
            var car = _this.getCar(_this.trackCarNo);
            if (car == null) {
                return;
            }
            var needMove = false;
            if (_this.trackCarLast == null) {
                needMove = true;
            }
            else if (_this.trackCarLast['lon'] != car.lon ||
                _this.trackCarLast['lat'] != car.lat ||
                _this.trackCarLast['info'] != car.info) {
                needMove = true;
            }
            _this.trackCarLast = {
                lon: car.lon,
                lat: car.lat,
                info: car.info,
            };
            if (needMove && _this.draw != null) {
                var gps = new Gps(car.lon, car.lat);
                if (_this.trackType == 'center') {
                    _this.draw.trackTo(gps);
                }
                else if (_this.trackType == 'view') {
                    var p = GpsTool.gps2point(gps);
                    if (GpsTool.checkPoint([p])) {
                        _this.draw.resetGpsDraw();
                        _this.draw.drawCar(true, 100);
                    }
                    else {
                        _this.draw.trackTo(gps);
                    }
                }
            }
        }, 100);
    };
    GpsDrawData.getCar = function (no) {
        var curCar = null;
        for (var i = 0; i < this.carList.length; i++) {
            if (this.carList[i].no == no) {
                curCar = this.carList[i];
                break;
            }
        }
        return curCar;
    };
    GpsDrawData.carUpdate = function (carList) {
        for (var _i = 0, carList_1 = carList; _i < carList_1.length; _i++) {
            var car = carList_1[_i];
            var curCar = this.getCar(car.no);
            if (curCar == null) {
                curCar = new Car();
                curCar.no = car.no;
                curCar.color = this.carColorList[this.carList.length % this.carColorList.length];
                this.carList.push(curCar);
            }
            for (var k in car) {
                if (curCar[k] !== undefined) {
                    curCar[k] = car[k];
                }
            }
        }
    };
    GpsDrawData.appendGpsList = function (gpsList) {
        this.carGpsList = this.carGpsList.concat(gpsList);
    };
    return GpsDrawData;
}());
GpsDrawData.topShapeList = [];
GpsDrawData.bottomShapeList = [];
GpsDrawData.mainShapeList = [];
GpsDrawData.draw = null;
GpsDrawData.carList = [];
GpsDrawData.carGpsList = [];
GpsDrawData.drawType = CarDrawType.line;
GpsDrawData.dataIndex = 0;
GpsDrawData.dataShowPaver = true;
GpsDrawData.paverColor = '#999';
GpsDrawData.dataColorRange = [];
GpsDrawData.trackCarNo = '';
GpsDrawData.trackType = 'center';
GpsDrawData.carColorList = [];
GpsDrawData.trackCarLast = null;
GpsDrawData.init();
