'use strict';

/* Directives */
var INTEGER_REGEXP = /^\-?\d+$/;
kulinarControllers.directive('integer', function() {
    return {
        require: 'ngModel',
        link: function($scope, $elm, $attrs, $ctrl) {
            $ctrl.$validators.integer = function(modelValue, viewValue) {

                console.log($attrs.integer);

                if (!isNaN(viewValue)) {
                    // it is valid
                    $scope.newrecept.multipleIngredients.items[$attrs.integer].invalid = 0;
                    return true;
                }

                if ($ctrl.$isEmpty(modelValue)) {
                    // consider empty models to be valid
                    //alert('пусто');
                    return true;
                }



                $scope.newrecept.multipleIngredients.items[$attrs.integer].invalid = 1;


                // it is invalid
                return false;
            };
        }
    };
});


kulinarControllers.directive('mymenu', function() {
    return {
        restrict: 'E',
        transclude: true,
        scope: {},
        controller: function($scope, $http, $location, $rootScope, Auth) {
            $rootScope.auth = new Auth();
            $rootScope.auth.login();
            $scope.auth = $rootScope.auth;
            //            $scope.auth.login();

            $scope.menu = [{
                class: 'active',
                text: 'Главная',
                link: '/home',
                show: 0
            }, {
                class: '',
                text: 'Добавить рецепт',
                link: '/recipes',
                show: 1
            }];
            $scope.getClass = function(path) {
                if ($location.path().substr(0, path.length) == path) {
                    return "active"
                } else {
                    return ""
                }
            }
            $scope.getShow = function(index) {
                if ($scope.menu[index].show) {
                    if ($scope.auth.user_login) {
                        return 1;
                    } else {
                        return 0;
                    }
                } else {
                    return 1
                }
            }



        },
        templateUrl: 'menu/menu.html'
    };
});


kulinarControllers.directive('activeLink', ['$location', function(location) {
    return {
        restrict: 'A',
        link: function(scope, element, attrs, controller) {
            var clazz = attrs.activeLink;
            var path = attrs.href;
            path = path.substring(1); //hack because path does not return including hashbang
            scope.location = location;
            scope.$watch('location.path()', function(newPath) {
                if (path === newPath) {
                    element.addClass(clazz);
                } else {
                    element.removeClass(clazz);
                }
            });
        }
    };
}]);

kulinarControllers.directive('setClassWhenAtTop', function($window) {
    var $win = angular.element($window); // wrap window object as jQuery object

    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            var topClass = attrs.setClassWhenAtTop, // get CSS class from directive's attribute value
                offsetTop = element.offset().top; // get element's offset top relative to document

            $win.on('scroll', function(e) {
                if ($win.scrollTop() >= offsetTop) {
                    element.addClass(topClass);
                } else {
                    element.removeClass(topClass);
                }
            });
        }
    };
});


kulinarControllers.directive('resize', function ($window) {
    return function (scope, element) {
        var w = angular.element($window);
        scope.getWindowDimensions = function () {
            return { 'h': w.height(), 'w': w.width() };
        };
        scope.$watch(scope.getWindowDimensions, function (newValue, oldValue) {
            scope.windowHeight = newValue.h;
            scope.windowWidth = newValue.w;

            scope.style = function () {
                return { 
                    'height': (newValue.h - 100) + 'px',
                    'width': (newValue.w - 100) + 'px' 
                };
            };

        }, true);

        w.bind('resize', function () {
            scope.$apply();
        });
    }
});

kulinarControllers.directive('ngThumb', ['$window', function($window) {
    var helper = {
        support: !!($window.FileReader && $window.CanvasRenderingContext2D),
        isFile: function(item) {
            return angular.isObject(item) && item instanceof $window.File;
        },
        isImage: function(file) {
            var type = '|' + file.type.slice(file.type.lastIndexOf('/') + 1) + '|';
            return '|jpg|png|jpeg|bmp|gif|'.indexOf(type) !== -1;
        }
    };

    return {
        restrict: 'A',
        template: '<canvas/>',
        link: function(scope, element, attributes) {
            if (!helper.support)
                return;

            var params = scope.$eval(attributes.ngThumb);

            if (!helper.isFile(params.file))
                return;
            if (!helper.isImage(params.file))
                return;

            var canvas = element.find('canvas');
            var reader = new FileReader();

            reader.onload = onLoadFile;
            reader.readAsDataURL(params.file);

            function onLoadFile(event) {
                var img = new Image();
                img.onload = onLoadImage;
                img.src = event.target.result;
            }

            function onLoadImage() {
                var width = params.width || this.width / this.height * params.height;
                var height = params.height || this.height / this.width * params.width;
                canvas.attr({
                    width: width,
                    height: height
                });
                canvas[0].getContext('2d').drawImage(this, 0, 0, width, height);
            }
        }
    };
}]);