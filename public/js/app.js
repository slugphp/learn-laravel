(function () {
    angular.module('inspinia', [
        'ui.router',                    // Routing
        'oc.lazyLoad',                  // ocLazyLoad
        'ui.bootstrap',                 // Ui Bootstrap
        'pascalprecht.translate',       // Angular Translate
        'ngIdle',                       // Idle timer
        'ngSanitize'                    // ngSanitize
    ])
})();

// 全局配置、方法

var AppConfig = {
    host: "http://172.17.9.92:8000",
    name: '后台管理系统',
    company: 'wilon.github.io',
    version: 'Beta 0.1',
    year: new Date().getFullYear()
}

function parseHost(path)
{
    return path;
    return "index.php?" + path;
}

function getCookie(name)
{
    var arr,reg = new RegExp("(^| )"+name+"=([^;]*)(;|$)");
    if (arr = decodeURIComponent(document.cookie).match(reg))
        return unescape(arr[2]);
    else
        return '';
}