(function () {

    var app = angular.module('inspinia');

    app.controller('LoginCtrl', function ($scope, $http, $state, Base64, locals) {

        $scope.AppConfig = AppConfig

        $scope.user = {};

        $http.post(parseHost('/auth/check'), {}, postConfig).success(function(data, status, headers, config) {
            $state.go('index.main');
        });

        $scope.doLogin = function() {
            var auth = Base64.encode($scope.user.name + '|' + $scope.user.password);
            $http.post(parseHost('/auth/login'), {
                auth: auth
            }, postConfig).success(function(data, status, headers, config) {
                $state.go('index.main');
                locals.set('admin_name', data);
            }).error(function(data, status, headers, config) {
                alert('密码错误，请确认后重试');
            });
        }
    });

    app.controller('MainCtrl', function ($scope, $state, $http, locals) {
        $http.post(parseHost('/auth/check'), {}, postConfig).error(function(data, status, headers, config) {
            $state.go('auth.login');
        });
        $scope.name = locals.get('admin_name') || '管理员';
        $scope.AppConfig = AppConfig;
    });

    app.controller('AdminListCtrl', function ($scope, $http, $filter, DTOptionsBuilder, DTColumnBuilder, $uibModal, notify, SweetAlert) {

        $scope.dtColumns = [
            DTColumnBuilder.newColumn('id', 'ID').notVisible().withOption('searchable', false),
            DTColumnBuilder.newColumn('name', '姓名'),
            DTColumnBuilder.newColumn('email', '邮箱'),
            DTColumnBuilder.newColumn('status', '状态').renderWith(function(data, type, row) {
                    switch (parseInt(data)) {
                        case 1:
                            var status = '<span ng-switch-when="1">管理员</span>';
                            break;
                        default:
                            var status = '<span ng-switch-default>被禁用</span>';
                            break;
                    }
                    return `<a class="changeAdminStatus" uib-tooltip="点击更改用户状态">${status}</a>`;
                }).withOption('searchable', false),
            DTColumnBuilder.newColumn('created_at', '添加时间'),
            DTColumnBuilder.newColumn(null, '操作').renderWith(function(data, type, row) {
                    return `<a class="editAdmin">编辑</a> | <a class="deleteAdmin"">删除</a>`;

                }).withOption('searchable', false).withOption('orderable', false)
        ];
        $scope.dtOptions = DTOptionsBuilderFn(DTOptionsBuilder, '/admin/list', 5, rowCallback);
        $scope.dtInstance = {};

        function rowCallback(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            $('td', nRow).unbind('click');
            $('td .changeAdminStatus', nRow).on('click', function() {
                $scope.changeAdminStatus(aData);
            });
            $('td .editAdmin', nRow).on('click', function() {
                $scope.modifyAdmin(aData);
            });
            $('td .deleteAdmin', nRow).on('click', function() {
                $scope.deleteAdmin(aData);
            });
            return nRow;
        }

        $scope.modifyAdmin = function (adminInfo) {
            var modalInstance = $uibModal.open({
                templateUrl: 'views/admin_modify.html?v3',
                controller: 'AdminModifyCtrl',
                windowClass: "animated fadeIn",
                size: 'big',
                resolve: {
                    adminInfo: function () {
                        return angular.copy(adminInfo);
                    },
                    dtInstance: function () {
                        return $scope.dtInstance;
                    }
                }
            });
        }

        $scope.changeAdminStatus = function(adminInfo) {
            var modalInstance = $uibModal.open({
                templateUrl: 'views/admin_status.html?v123',
                controller: 'AdminModifyCtrl',
                windowClass: "animated fadeIn",
                size: 'big',
                resolve: {
                    adminInfo: function () {
                        return angular.copy(adminInfo);
                    },
                    dtInstance: function () {
                        return $scope.dtInstance;
                    }
                }
            });
        }

        $scope.deleteAdmin = function(adminInfo) {
            SweetAlert.swal({
                title: "请确认删除？",
                text: "该用户("+adminInfo.name+":"+adminInfo.email+")所有信息将被删除！",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "确认删除",
                cancelButtonText: "好吧，取消",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function (isConfirm) {
                if (isConfirm) {
                    new Http($http, notify).post('/admin/delete', adminInfo, function(data, status, headers, config) {
                            $scope.dtInstance.reloadData(null, typeof(adminInfo.id) == "undefined");
                            notify({
                                message: '删除成功！',
                                classes: 'alert-danger',
                            });
                    });
                }
            });
        };
    })
    .controller('AdminModifyCtrl', function ($scope, $http, $uibModalInstance, notify, $sce, adminInfo, dtInstance) {

        $scope.cancel = function () {
            $uibModalInstance.close();
        };

        adminInfo.password = typeof adminInfo.password == 'undefined' ? '' : '******';
        $scope.adminInfo = adminInfo;

        if (typeof $scope.adminInfo.id == 'undefined') {
            $scope.title = '添加用户';
        } else {
            $scope.title = '编辑用户';
        }

        $scope.adminModifySubmit = function() {
            if ($scope.admin_form.$valid) {
                var url = typeof($scope.adminInfo.id) == "undefined" ? "/admin/add" : "/admin/edit";
                new Http($http, notify).post(url, $scope.adminInfo, function(data, status, headers, config) {
                        dtInstance.reloadData(null, typeof($scope.adminInfo.id) == "undefined");
                        $uibModalInstance.close();
                });
            } else {
                $scope.admin_form.submitted = true;
            }
        }

        $scope.changeStatus = function(status) {
            new Http($http, notify).post('/admin/status', $scope.adminInfo, function(data, status, headers, config) {
                    dtInstance.reloadData(null, typeof($scope.adminInfo.id) == "undefined");
                    $uibModalInstance.close();
            });
        }
    });

    function DTOptionsBuilderFn(DTOptionsBuilder, url, orderNum, rowCallback, fnCallback) {
        return DTOptionsBuilder
            .fromSource(url)
            .withFnServerData(function (sSource, aoData, fnCallback, oSettings) {
                oSettings.jqXHR = $.ajax({
                    'dataType': 'json',
                    'type': 'GET',
                    'url': url,
                    'data': {
                        draw: aoData[0].value,
                        columns: aoData[1].value,
                        order: aoData[2].value,
                        start: aoData[3].value,
                        length: aoData[4].value,
                        search: aoData[5].value.value,
                    },
                    'success': fnCallback,
                    'error': function () {
                        window.location.reload();
                    }
                });
            })
            .withDataProp('data')
            // .withOption('processing', true)
            .withOption('rowCallback', rowCallback)
            .withOption('serverSide', true)
            .withOption('order', [orderNum, 'desc'])
            .withLanguage(datatablesLangCn);
    }

    var datatablesLangCn = {
        "sProcessing": "处理中...",
        "sLengthMenu": "每页显示 _MENU_ 项结果",
        "sZeroRecords": "没有匹配结果",
        "sInfo": "本页显示第 _START_ 至 _END_ 项结果，共 _TOTAL_ 项",
        "sInfoEmpty": "显示第 0 至 0 项结果，共 0 项",
        "sInfoFiltered": "(由 _MAX_ 项结果过滤)",
        "sInfoPostFix": "",
        "sSearch": "搜索:",
        "sUrl": "",
        "sEmptyTable": "载入中...",
        "sLoadingRecords": "载入中...",
        "sInfoThousands": ",",
        "oPaginate": {
            "sFirst": "首页",
            "sPrevious": "上页",
            "sNext": "下页",
            "sLast": "末页"
        },
        "oAria": {
            "sSortAscending": ": 以升序排列此列",
            "sSortDescending": ": 以降序排列此列"
        }
    }

    var postConfig = {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        transformRequest: function(data){
            return $.param(data);
        }
    }
    function Http($http, notify) {
        this.$http = $http;
        this.notify = notify;
        this.post = function (url, params, success) {
            var $this = this;
            $this.$http.post(url, params, postConfig).success(function(data, status, headers, config) {
                success(data, status, headers, config);
            }).error(function(data, status, headers, config) {
                if (data.length < 500) {
                    $this.notify({
                        message: typeof(data) == 'undefined' ? 'inner error' : data,
                        classes: 'alert-danger',
                    });
                }
            });
            return this;
        }
    }


})();