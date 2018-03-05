function GpsPoint(lon, lat, height)
{
    this.RE = 6371004;	// 地球半径，单位 米
    this.metersPerLat = 0;   // 1纬度的距离
    this.lon = lon ? lon : 0;
    this.lat = lat ? lat : 0;
    this.height = height ? height : 0;

    // 返回当前点到指定点的平面距离
    this.distance = function(otherGpsPoint)
    {
        var lonSpan = Math.abs(this.lon - otherGpsPoint.lon);
        var latSpan = Math.abs(this.lat - otherGpsPoint.lat);
        var middleLat = (this.lat + otherGpsPoint.lat) / 2;

        var EWmeters = lonSpan * this.getMetersPerLon(middleLat);
        var NSmeters = latSpan * this.getMetersPerLat();

        var distanceMeters = Math.pow(Math.pow(EWmeters, 2) + Math.pow(NSmeters, 2), 0.5);
        return distanceMeters;
    };

    // 返回当前点到指定点的三维距离
    this.distance3d = function(otherGpsPoint)
    {
        var distance = this.distance(otherGpsPoint);
        return Math.pow(Math.pow(distance, 2) + Math.pow(this.height - otherGpsPoint.height, 2), 0.5);
    }

    // 返回从当前点到指定点的连线， 相对于正北的角度值
    this.getNorthAngle = function(otherGpsPoint)
    {
        if(this.lon == otherGpsPoint.lon) return this.lat <= otherGpsPoint.lat ? 0 : 180;
        if(this.lat == otherGpsPoint.lat) return this.lon <= otherGpsPoint.lon ? 90 : 270;

        var lonSpan = Math.abs(this.lon - otherGpsPoint.lon);
        var latSpan = Math.abs(this.lat - otherGpsPoint.lat);
        var middleLat = (this.lat + otherGpsPoint.lat) / 2;

        var EWmeters = lonSpan * this.getMetersPerLon(middleLat);
        var NSmeters = latSpan * this.getMetersPerLat();

        var northRadian = Math.atan(EWmeters / NSmeters);
        var northAngle = GpsPoint.radiaToAngle(northRadian);

        if((this.lon < otherGpsPoint.lon) && (this.lat > otherGpsPoint.lat)) northAngle = 180 - northAngle;
        else if ((this.lon > otherGpsPoint.lon) && (this.lat > otherGpsPoint.lat)) northAngle = northAngle + 180;
        else if ((this.lon > otherGpsPoint.lon) && (this.lat < otherGpsPoint.lat)) northAngle = 360 - northAngle;
        while(northAngle < 0) northAngle += 360;
        while(northAngle > 360) northAngle -= 360;
        return northAngle;
    };

    // 返回向指定角度移动指定距离后的点
    // angle: 表示以正北为0度， 顺时针旋转的角度值
    this.move = function(meters, angle, returnNewObj)
    {
        var radian = GpsPoint.angleToRadia(angle);
        var lonMeters = meters * Math.sin(radian);
        var latMeters = meters * Math.cos(radian);

        var latSpan = latMeters / this.getMetersPerLat();
        var lonSpan = lonMeters / this.getMetersPerLon(this.lat + latSpan / 2);

        if(returnNewObj) return new GpsPoint(this.lon + lonSpan, this.lat + latSpan);
        this.lon += lonSpan;
        this.lat += latSpan;
        return this;
    };

    this.getMetersPerLat = function()
    {
        if(this.metersPerLat == 0) this.metersPerLat = Math.PI * this.RE * 2.0 / 360.0;
        return this.metersPerLat;
    };

    // 在指定纬度下， 1经度的距离
    this.getMetersPerLon = function(lat)
    {
        var radian = GpsPoint.angleToRadia(Math.abs(lat))
        var RE_lat = this.RE * Math.cos(radian);
        return Math.PI * RE_lat * 2.0 / 360.0;
    };
};
GpsPoint.angleToRadia = function(angle) { return angle * Math.PI / 180.0; };
GpsPoint.radiaToAngle = function(radia) { return radia * 180 / Math.PI; };
