<html ng-app="inspinia">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title page-title></title>
        <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
        <link id="loadBefore" href="static/slug-laravel.min.css" rel="stylesheet">
    </head>
    <body ng-controller="MainCtrl" class="{{$state.current.data.specialClass}}" landing-scrollspy id="page-top">
        <div ui-view></div>
        <script src="static/slug-laravel-autoload.min.js"></script>
        <script src="static/slug-laravel.min.js"></script>
        <script src="js/controllers.js"></script>
    </body>
</html>