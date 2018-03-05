var JSql = (function () {
    function JSql(_dbName, size) {
        if (_dbName === void 0) { _dbName = 'db'; }
        if (size === void 0) { size = 20 * 1024 * 1024; }
        this.db = null;
        this.isRun = false;
        this.db = openDatabase(_dbName, '1.0', 'ENH_sqlite DB', size);
    }
    JSql.prototype.run = function (sql, fnSuccess, fnError) {
        if (fnSuccess === void 0) { fnSuccess = null; }
        if (fnError === void 0) { fnError = null; }
        if (this.isRun)
            return;
        this.isRun = true;
        var my = this;
        console.time('run_sql');
        this.db.transaction(function (tx) {
            tx.executeSql(sql, [], function (tx, res) {
                my.isRun = false;
                console.timeEnd('run');
                if (fnSuccess) {
                    fnSuccess(res);
                }
            }, function (tx, err) {
                my.isRun = false;
                console.timeEnd('run');
                console.error(err);
                if (fnError) {
                    fnError(err);
                }
            });
        });
    };
    JSql.prototype.runList = function (sql_list) {
        this.db.transaction(function (tx) {
            for (var _i = 0, sql_list_1 = sql_list; _i < sql_list_1.length; _i++) {
                var sql = sql_list_1[_i];
                tx.executeSql(sql);
            }
        });
    };
    return JSql;
}());
