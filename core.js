// public/core.js
var evergreenDoc = angular.module('evergreenDoc', []);


function mainController($scope, $http, $timeout) {
	$scope.formData = {};




	// when landing on the page, get all todos and show them

	

    (function tick() {
       $http({method: 'GET', url: '/statuses'})
		.success(function(data){
			$scope.statuses = data;
			$timeout(tick, 1000);
			console.log("yESSS!");
		});
            
        
    })();


}

